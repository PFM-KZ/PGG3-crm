<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use Exception;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\SettlementPackage;
use Wecoders\EnergyBundle\Entity\SettlementPackageRecord;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\PackageToGenerateModel;
use Wecoders\EnergyBundle\Service\SettlementModel;
use Wecoders\EnergyBundle\Service\SettlementPackageModel;
use Wecoders\EnergyBundle\Service\SettlementPackageRecordModel;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;

class SettlementPackageStatusGenerate extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $settlementPackageModel;

    private $invoiceModel;

    private $initializer;

    private $settlementPackageRecordModel;

    private $settlementModel;

    private $easyAdminModel;

    private $invoiceTemplateModel;

    private $invoiceBundleInvoiceModel;

    public function __construct(ContainerInterface $container, EntityManager $em, SettlementModel $settlementModel, SettlementPackageModel $settlementPackageModel, SettlementPackageRecordModel $settlementPackageRecordModel, InvoiceModel $invoiceModel, Initializer $initializer, EasyAdminModel $easyAdminModel, InvoiceTemplateModel $invoiceTemplateModel, \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->settlementPackageModel = $settlementPackageModel;
        $this->invoiceModel = $invoiceModel;
        $this->initializer = $initializer;
        $this->settlementPackageRecordModel = $settlementPackageRecordModel;
        $this->settlementModel = $settlementModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->invoiceTemplateModel = $invoiceTemplateModel;
        $this->invoiceBundleInvoiceModel = $invoiceBundleInvoiceModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:settlement-package-status-generate')
            ->setDescription('Generate documents from package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('settlement_package_status_generate');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();



        /** @var SettlementPackage $settlementPackage */
        $settlementPackage = $this->settlementPackageModel->getSingleRecordByStatus(SettlementPackageModel::STATUS_IN_PROCESS);
        if (!$settlementPackage) {
            dump('No packages with "to process" status.');
            die;
        }

        // Here is status generate,
        // that means generate documents, and add them as generated
        // next status gonna change
        $settlementPackageRecords = $em->getRepository('WecodersEnergyBundle:SettlementPackageRecord')->findBy(['settlementPackage' => $settlementPackage]);

        /** @var SettlementPackageRecord $settlementPackageRecord */
        $actualProcessedRecord = null;
        foreach ($settlementPackageRecords as $settlementPackageRecord) {
            if ($settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_GENERATE) {
                $actualProcessedRecord = $settlementPackageRecord;
                break;
            }
        }


        if (!$actualProcessedRecord) {
            dump('No records to generate');
            die;
        }

        $em->getConnection()->beginTransaction();
        try {
            $invoiceSettlement = $actualProcessedRecord->getInvoiceSettlement();
            $invoiceEstimatedSettlement = $actualProcessedRecord->getInvoiceEstimatedSettlement();
            if (!$invoiceSettlement && !$invoiceEstimatedSettlement) {
                throw new \Exception('Rekord nie ma przypisanej faktury');
            }

            $entityName = $invoiceSettlement ? 'InvoiceSettlementEnergy' : 'InvoiceEstimatedSettlementEnergy';
            $invoice = $invoiceSettlement ? $actualProcessedRecord->getInvoiceSettlement() : $actualProcessedRecord->getInvoiceEstimatedSettlement();



            $kernelRootDir = $this->container->get('kernel')->getRootDir();

            /** @var InvoiceTemplate $invoiceTemplate */
            $invoiceTemplate = $invoice->getInvoiceTemplate();
            if (!$invoiceTemplate || !$invoiceTemplate->getFilePath() || !file_exists($this->invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath()))) {
                throw new \Exception('Szablon faktury nie istnieje (sprawdź czy rekord faktury ma ustawiony szablon oraz czy rekord szablonu ma wgrany plik).');
            }
            $templateAbsolutePath = $this->invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath());

            $directoryRelative = $this->easyAdminModel->getEntityDirectoryRelativeByEntityName($entityName);
            $invoicePath = $this->invoiceBundleInvoiceModel->fullInvoicePath($kernelRootDir, $invoice, $directoryRelative);


            $clientAndContract = $this->settlementModel->getClientWithContractByPp($actualProcessedRecord->getPp());

            $contract = $clientAndContract->getContract();
            if (!$contract) {
                throw new \Exception('Nie znaleziono umowy na podstawie numeru na fakturze');
            }

            $generateDocumentMethod = $this->easyAdminModel->getEntityGenerateDocumentMethodByEntityName($entityName);

            $fileGenerated = false;
            for ($i = 0; $i < 3; $i++) {
                $this->invoiceModel->$generateDocumentMethod($invoice, $invoicePath, $templateAbsolutePath, $contract->getType());
                dump('Generate file attempt.');
                if (file_exists($invoicePath . '.pdf')) {
                    $fileGenerated = true;
                    break;
                }
            }

            if ($fileGenerated) {
                $actualProcessedRecord->setStatus(PackageToGenerateModel::STATUS_COMPLETE);
                $em->persist($actualProcessedRecord);
                $em->flush();
            } else {
                throw new \Exception('After few attempts file were not generated.');
            }

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            $reFetchedSettlementPackageRecord = $this->settlementPackageRecordModel->getRecord($actualProcessedRecord->getId());
            $reFetchedSettlementPackageRecord->setErrorMessage($e->getMessage() . ' - on line: ' . $e->getLine());
            $reFetchedSettlementPackageRecord->setStatus(SettlementPackageRecordModel::STATUS_GENERATE_ERROR);
            $em->persist($actualProcessedRecord);
            $em->flush();

            dump('Error occoured: ' . $e->getMessage());
        }



        $em->clear();
        dump('Success');
        // release lock, so command can be used again
        $lock->release();


        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:settlement-package-status-generate');
    }

}