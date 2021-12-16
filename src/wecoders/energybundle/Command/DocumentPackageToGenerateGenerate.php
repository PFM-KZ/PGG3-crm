<?php

namespace Wecoders\EnergyBundle\Command;

use AppBundle\Service\PdfHelper;
use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ClientAndContract;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\DistributorModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerateRecord;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\EnergyBundle\Service\CustomDocumentTemplateModel;
use Wecoders\EnergyBundle\Service\DocumentModel;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateModel;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateRecordModel;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\PriceListModel;
use Wecoders\EnergyBundle\Service\SettlementModel;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;

class DocumentPackageToGenerateGenerate extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $invoiceModel;

    private $initializer;

    private $settlementModel;

    private $easyAdminModel;

    private $documentPackageToGenerateModel;

    private $documentPackageToGenerateRecordModel;

    private $customDocumentTemplateModel;

    private $pdfHelper;

    private $contractAccessor;

    private $distributorModel;

    private $priceListModel;

    private $energyBundleInvoiceModel;

    private $documentModel;

    private $invoiceTemplateModel;

    public function __construct(
        ContainerInterface $container,
        EntityManager $em,
        SettlementModel $settlementModel,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceModel,
        Initializer $initializer,
        EasyAdminModel $easyAdminModel,
        DocumentPackageToGenerateModel $documentPackageToGenerateModel,
        DocumentPackageToGenerateRecordModel $documentPackageToGenerateRecordModel,
        CustomDocumentTemplateModel $customDocumentTemplateModel,
        PdfHelper $pdfHelper,
        ClientModel $clientModel,
        ContractAccessor $contractAccessor,
        PriceListModel $priceListModel,
        DistributorModel $distributorModel,
        InvoiceModel $energyBundleInvoiceModel,
        DocumentModel $documentModel,
        InvoiceTemplateModel $invoiceTemplateModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->invoiceModel = $invoiceModel;
        $this->initializer = $initializer;
        $this->settlementModel = $settlementModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->documentPackageToGenerateModel = $documentPackageToGenerateModel;
        $this->documentPackageToGenerateRecordModel = $documentPackageToGenerateRecordModel;
        $this->customDocumentTemplateModel = $customDocumentTemplateModel;
        $this->pdfHelper = $pdfHelper;
        $this->contractAccessor = $contractAccessor;
        $this->priceListModel = $priceListModel;
        $this->distributorModel = $distributorModel;
        $this->energyBundleInvoiceModel = $energyBundleInvoiceModel;
        $this->documentModel = $documentModel;
        $this->invoiceTemplateModel = $invoiceTemplateModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:document-package-to-generate-generate')
            ->setDescription('Generate documents from package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->setUpLock($output);

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();

        /** @var DocumentPackageToGenerateRecord $actualGenerateRecord */
        $actualGenerateRecord = $this->getActualGeneratedRecord();

        /** @var DocumentPackageToGenerate $package */
        $package = $actualGenerateRecord->getPackage();

        if ($this->allDocumentsWereGenerated($package)) {
            $this->resetPackageStatus($package);
            dump('No records to generate');
            die;
        }


        $this->em->getConnection()->beginTransaction();
        try {
            $entity = $this->documentModel->getMappedOptionByValue($package->getGeneratedDocumentEntity());
            $generatedDocumentEntityConfig = $this->easyAdminModel->getEntityConfigByEntityName($entity);
            $document = $this->em->getRepository($generatedDocumentEntityConfig['class'])->find($actualGenerateRecord->getGeneratedDocumentId());
            if (!$document) {
                throw new \Exception('Dokument nie istnieje');
            }

            /** @var InvoiceTemplate $invoiceTemplate */
            $invoiceTemplate = $document->getInvoiceTemplate();
            if (!$invoiceTemplate || !$invoiceTemplate->getFilePath() || !file_exists($this->invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath()))) {
                throw new \Exception('Szablon faktury nie istnieje (sprawdź czy rekord faktury ma ustawiony szablon oraz czy rekord szablonu ma wgrany plik).');
            }
            $templateAbsolutePath = $this->invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath());

            $directoryRelative = $generatedDocumentEntityConfig['directoryRelative'];
            $invoicePath = $this->invoiceModel->fullInvoicePath($kernelRootDir, $document, $directoryRelative);

            $fileGenerated = false;
            for ($i = 0; $i < 3; $i++) {
                $this->energyBundleInvoiceModel->generateInvoiceProformaCorrection($document, $invoicePath, $templateAbsolutePath, $document->getType());
                dump('Generate file attempt.');
                if (file_exists($invoicePath . '.pdf')) {
                    $fileGenerated = true;
                    break;
                }
            }

            if ($fileGenerated) {
                $actualGenerateRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_COMPLETE);
                $this->em->persist($actualGenerateRecord);
                $this->em->flush();
            } else {
                throw new \Exception('After few attempts file were not generated.');
            }

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();

            $package->setStatus(DocumentPackageToGenerateModel::STATUS_GENERATE_ERROR);
            $this->em->persist($package);
            $this->em->flush();

            dump('Error occoured: ' . $e->getMessage());
        }


        $this->em->clear();
        dump('Success');

        $lock->release();

        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:document-package-to-generate-generate');
    }

    /**
     * @param $package
     */
    protected function resetPackageStatus(DocumentPackageToGenerate $package)
    {
        $package->setStatus(DocumentPackageToGenerateModel::STATUS_WAITING_TO_PROCESS);
        $this->em->persist($package);
        $this->em->flush($package);
    }

    /**
     * @return null|object
     */
    private function getActualGeneratedRecord()
    {
        $actualGenerateRecord = $this->documentPackageToGenerateRecordModel->getSingleRecordByStatus(DocumentPackageToGenerateRecordModel::STATUS_GENERATE);
        if (!$actualGenerateRecord) {
            dump('No record with "to generate" status.');
            die;
        }
        return $actualGenerateRecord;
    }

    /**
     * @param $package
     * @return bool
     */
    private function allDocumentsWereGenerated(DocumentPackageToGenerate $package)
    {
        return count($package->getPackageRecords()) == $package->getCountCompleted() + $package->getCountError();
    }

    /**
     * @param OutputInterface $output
     * @return LockHandler
     */
    private function setUpLock(OutputInterface $output)
    {
        $lock = new LockHandler('document_package_to_generate_generate');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            die;
        }
        return $lock;
    }

}