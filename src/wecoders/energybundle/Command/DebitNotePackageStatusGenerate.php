<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\DebitNotePackage;
use Wecoders\EnergyBundle\Entity\DebitNotePackageRecord;
use Wecoders\EnergyBundle\Service\DebitNoteModel;
use Wecoders\EnergyBundle\Service\DebitNotePackageModel;
use Wecoders\EnergyBundle\Service\DebitNotePackageRecordModel;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\SettlementModel;
use Wecoders\EnergyBundle\Service\SettlementPackageModel;
use Wecoders\EnergyBundle\Service\SettlementPackageRecordModel;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;

class DebitNotePackageStatusGenerate extends Command
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

    private $debitNotePackageModel;

    private $debitNotePackageRecordModel;

    private $debitNoteModel;

    public function __construct(
        ContainerInterface $container,
        EntityManager $em,
        SettlementModel $settlementModel,
        SettlementPackageModel $settlementPackageModel,
        SettlementPackageRecordModel $settlementPackageRecordModel,
        DebitNotePackageModel $debitNotePackageModel,
        DebitNotePackageRecordModel $debitNotePackageRecordModel,
        InvoiceModel $invoiceModel,
        Initializer $initializer,
        EasyAdminModel $easyAdminModel,
        InvoiceTemplateModel $invoiceTemplateModel,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel,
        DebitNoteModel $debitNoteModel
    )
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
        $this->debitNotePackageModel = $debitNotePackageModel;
        $this->debitNotePackageRecordModel = $debitNotePackageRecordModel;
        $this->debitNoteModel = $debitNoteModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:debit-note-package-status-generate')
            ->setDescription('Generate documents from package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('debit_note_package_status_generate');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();



        /** @var DebitNotePackage $package */
        $package = $this->debitNotePackageModel->getSingleRecordByStatus(DebitNotePackageModel::STATUS_IN_PROCESS);
        if (!$package) {
            dump('No packages with "to process" status.');
            die;
        }

        // Here is status generate,
        // that means generate documents, and add them as generated
        // next status gonna change

        /** @var DebitNotePackageRecord $packageRecord */
        $actualProcessedRecord = null;
        foreach ($package->getPackageRecords() as $packageRecord) {
            if ($packageRecord->getStatus() == DebitNotePackageRecordModel::STATUS_GENERATE) {
                $actualProcessedRecord = $packageRecord;
                break;
            }
        }

        if (!$actualProcessedRecord) {
            dump('No records to generate');
            die;
        }

        $em->getConnection()->beginTransaction();
        try {
            if (!$actualProcessedRecord->getDocument()) {
                throw new \Exception('Rekord nie ma przypisanego dokumentu');
            }

            $entityName = 'DebitNote';
            $kernelRootDir = $this->container->get('kernel')->getRootDir();

            /** @var InvoiceTemplate $documentTemplate */
            $documentTemplate = $actualProcessedRecord->getDocumentTemplate();
            $documentTemplateAbsolutePath = $this->invoiceTemplateModel->getTemplateAbsolutePath($documentTemplate->getFilePath());

            $directoryRelative = $this->easyAdminModel->getEntityDirectoryRelativeByEntityName($entityName);
            $documentPath = $this->debitNoteModel->getDocumentPath($actualProcessedRecord->getDocument(), $directoryRelative);

            $brand = $actualProcessedRecord->getBrand();
            $logoAbsolutePath = $this->debitNoteModel->getLogoAbsolutePath($brand);

            $fileGenerated = false;
            for ($i = 0; $i < 3; $i++) {
                $this->debitNoteModel->generateDebitNoteDocument(
                    $actualProcessedRecord->getDocument(),
                    $documentPath,
                    $documentTemplateAbsolutePath,
                    $logoAbsolutePath,
                    $actualProcessedRecord->getContractType()
                );

                if (file_exists($documentPath . '.pdf')) {
                    $fileGenerated = true;
                    break;
                }
            }

            if ($fileGenerated) {
                $actualProcessedRecord->setStatus(DebitNotePackageRecordModel::STATUS_COMPLETE);
                $em->persist($actualProcessedRecord);
                $em->flush();
            } else {
                throw new \Exception('After few attempts file were not generated.');
            }

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            $reFetchedDebitNotePackageRecord = $this->debitNotePackageRecordModel->getRecord($actualProcessedRecord->getId());
            $reFetchedDebitNotePackageRecord->setErrorMessage($e->getMessage() . ' - on line: ' . $e->getLine());
            $reFetchedDebitNotePackageRecord->setStatus(DebitNotePackageRecordModel::STATUS_GENERATE_ERROR);
            $em->persist($actualProcessedRecord);
            $em->flush();

            dump('Error occoured: ' . $e->getMessage());
        }



        $em->clear();
        dump('Success');
        // release lock, so command can be used again
        $lock->release();


        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:debit-note-package-status-generate');
    }

}