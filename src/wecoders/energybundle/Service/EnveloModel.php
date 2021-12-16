<?php

namespace Wecoders\EnergyBundle\Service;

use AppBundle\Service\PdfHelper;
use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\Settings\System;
use GCRM\CRMBundle\Service\ZipModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\DebitNote;
use Wecoders\EnergyBundle\Entity\DocumentBankAccountChange;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerateRecord;
use Wecoders\EnergyBundle\Entity\ICollectiveMarkable;
use Wecoders\EnergyBundle\Entity\InvoiceBase;
use Wecoders\EnergyBundle\Entity\InvoiceCollective;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Service\ListSearcher\PaymentRequest;

class EnveloModel
{
    const MAX_FILES_IN_PACKAGE = 25;
    const MAX_PAYMENT_REQUEST_FILES_IN_PACKAGE = 500;
    const MAX_SETTLEMENT_FILES_IN_PACKAGE = 250;
    const MAX_DEBIT_NOTE_FILES_IN_PACKAGE = 100;
    const MAX_DOCUMENT_PACKAGE_FILES_IN_PACKAGE = 40;
    const MAX_DOCUMENT_PACKAGE_TYPE_CORRECTION_FILES_IN_PACKAGE = 25;

    private $em;
    private $zipModel;
    private $container;
    private $invoiceModel;
    private $easyAdminModel;
    private $paymentRequestModel;
    private $settlementModel;
    private $documentPathReader;
    private $documentBankAccountChangeModel;
    private $debitNoteModel;
    private $systemSettings;
    private $pdfHelper;

    public function __construct(
        EntityManager $em,
        ZipModel $zipModel,
        ContainerInterface $container,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceModel,
        EasyAdminModel $easyAdminModel,
        PaymentRequestModel $paymentRequestModel,
        SettlementModel $settlementModel,
        DocumentPathReader $documentPathReader,
        DocumentBankAccountChangeModel $documentBankAccountChangeModel,
        DebitNoteModel $debitNoteModel,
        System $systemSettings,
        PdfHelper $pdfHelper,
        DocumentPackageToGenerateRecordModel $documentPackageToGenerateRecordModel
    )
    {
        $this->em = $em;
        $this->zipModel = $zipModel;
        $this->container = $container;
        $this->invoiceModel = $invoiceModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->paymentRequestModel = $paymentRequestModel;
        $this->settlementModel = $settlementModel;
        $this->documentPathReader = $documentPathReader;
        $this->documentBankAccountChangeModel = $documentBankAccountChangeModel;
        $this->debitNoteModel = $debitNoteModel;
        $this->systemSettings = $systemSettings;
        $this->pdfHelper = $pdfHelper;
        $this->documentPackageToGenerateRecordModel = $documentPackageToGenerateRecordModel;
    }

    public function generate($folderName, $invoices)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container = $this->container;

        $kernelRootDir = $container->get('kernel')->getRootDir();
        $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);

        $clients = $this->mergeInvoicesWithClients($invoices);

        $enveloOutputDir = $kernelRootDir . '/../var/data/envelo/' . $folderName;
        if (!file_exists($enveloOutputDir)) {
            mkdir($enveloOutputDir, 0777, true);
        }

        $invoicesOutputDir = $enveloOutputDir . '/invoices';
        if (!file_exists($invoicesOutputDir)) {
            mkdir($invoicesOutputDir, 0777, true);
        }

        $zipOutputDir = $enveloOutputDir . '/zip';
        if (!file_exists($zipOutputDir)) {
            mkdir($zipOutputDir, 0777, true);
        }

        $dataOutputDir = $enveloOutputDir . '/data';
        if (!file_exists($dataOutputDir)) {
            mkdir($dataOutputDir, 0777, true);
        }

        $spreadsheetStartIndexFrom = 3;
        $outputFilesPaths = [];
        $enveloCounter = 1;
        $enveloPackageNumber = 1;
        $index = 0;
        $lastIterationIndex =  count($clients) - 1;
        $zipFiles = [];
        foreach ($clients as $itemData) {
            $invoiceNumbers = $this->fetchInvoiceAiNumbersFromInvoices($itemData['invoices']);

            // adds oze if exist
            if ($itemData['bankAccountChange']) {
                $invoiceNumbers = 'ZNR-' . $invoiceNumbers;
            }
            if ($itemData['oze']) {
                $invoiceNumbers = 'OZE-' . $invoiceNumbers;
            }

            $outputFilePath = $invoicesOutputDir. '/' . $invoiceNumbers . '.pdf';
            $outputFilesPaths[] = $outputFilePath;
            $invoicesFromClientAbsolutePaths = $this->getInvoicesAbsoultePaths(
                $kernelRootDir,
                $itemData['invoices'],
                \Wecoders\EnergyBundle\Service\InvoiceModel::ROOT_RELATIVE_INVOICES_PROFORMA_PATH
            );

            // adds oze if exist
            if ($itemData['bankAccountChange']) {
                $invoicesFromClientAbsolutePaths = array_prepend($invoicesFromClientAbsolutePaths, $itemData['bankAccountChange']);
            }
            if ($itemData['oze']) {
                $invoicesFromClientAbsolutePaths = array_prepend($invoicesFromClientAbsolutePaths, $itemData['oze']);
            }

            $this->pdfHelper->mergePdfFiles($invoicesFromClientAbsolutePaths, $outputFilePath);

            if ($enveloCounter == self::MAX_FILES_IN_PACKAGE || $index == $lastIterationIndex) { // Save and reset data
                $row = $this->dataRow($itemData['invoices'][0], $invoiceNumbers);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);

                $enveloFilename = 'envelo_' . $enveloPackageNumber . '.xls';
                $enveloAbsolutePath = $dataOutputDir . '/' . $enveloFilename;
                $this->saveSpreadsheet($spreadsheet, $enveloAbsolutePath);
                $outputFilesPaths[] = $enveloAbsolutePath;

                $zipFilename = 'envelo' . $enveloPackageNumber . '.zip';
                $this->zipModel->generate($outputFilesPaths, $zipOutputDir . '/' . $zipFilename);
                $zipFiles[] = $zipOutputDir . '/' . $zipFilename;

                $enveloPackageNumber++;

                $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);
                $enveloCounter = 0;
                $spreadsheetStartIndexFrom = 3;
                $outputFilesPaths = [];
            } else {
                $row = $this->dataRow($itemData['invoices'][0], $invoiceNumbers);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);
            }

            $enveloCounter++;
            $index++;
        }

        $this->zipModel->download($zipFiles, 'envelo', false, 'envelo', true);
        $this->removeDirectory($enveloOutputDir); // removes generated envelo files
        die;
    }

    public function generateForPaymentRequest($folderName, $objects)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container = $this->container;

        $kernelRootDir = $container->get('kernel')->getRootDir();
        $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);

        $enveloOutputDir = $kernelRootDir . '/../var/data/envelo-payment-request/' . $folderName;
        if (!file_exists($enveloOutputDir)) {
            mkdir($enveloOutputDir, 0777, true);
        }

        $objectsOutputDir = $this->easyAdminModel->getEntityDirectoryByEntityName('PaymentRequest');
        if (!file_exists($objectsOutputDir)) {
            mkdir($objectsOutputDir, 0777, true);
        }

        $zipOutputDir = $enveloOutputDir . '/zip';
        if (!file_exists($zipOutputDir)) {
            mkdir($zipOutputDir, 0777, true);
        }

        $dataOutputDir = $enveloOutputDir . '/data';
        if (!file_exists($dataOutputDir)) {
            mkdir($dataOutputDir, 0777, true);
        }

        $spreadsheetStartIndexFrom = 3;
        $outputFilesPaths = [];
        $enveloCounter = 1;
        $enveloPackageNumber = 1;
        $index = 0;
        $lastIterationIndex =  count($objects) - 1;
        $zipFiles = [];
        /** @var \Wecoders\EnergyBundle\Entity\PaymentRequest $object */
        foreach ($objects as $object) {
            $objectNumber = $object->getId();

            $filenamePrefix = '';
            // sent only to clients that already had invoices (with old number)
            $documentBankAccountChange = null;
            $documentBankAccountChangePath = null;
            if ($this->documentBankAccountChangeModel->canBeAppliedToDocument($object->getBadgeId(), $object->getNumber())) {
                $filenamePrefix = 'ZNR-';
                /** @var DocumentBankAccountChange $documentBankAccountChange */
                $documentBankAccountChange = $this->documentBankAccountChangeModel->getRecordByBadgeId($object->getBadgeId());
                $documentBankAccountChangePath = $documentBankAccountChange->getFilePath();
            }

            $outputFilePath = $this->paymentRequestModel->getDocumentPath($object, $objectsOutputDir) . '.pdf';

            if ($filenamePrefix) {
                $tmpOutputFilePath = $this->paymentRequestModel->getDocumentPath($object, $objectsOutputDir, $filenamePrefix) . '.pdf';
                $tmpOutputForMerge = [];
                $tmpOutputForMerge[] = $outputFilePath;

                $tmpOutputForMerge = array_prepend($tmpOutputForMerge, $documentBankAccountChangePath);
                $this->pdfHelper->mergePdfFiles($tmpOutputForMerge, $tmpOutputFilePath);
                $outputFilePath = $tmpOutputFilePath;
            }
            $outputFilesPaths[] = $outputFilePath;


            if ($enveloCounter == self::MAX_PAYMENT_REQUEST_FILES_IN_PACKAGE || $index == $lastIterationIndex) { // Save and reset data
                $row = $this->dataRowPaymentRequest($object, $objectNumber);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);

                $enveloFilename = 'envelo_' . $enveloPackageNumber . '.xls';
                $enveloAbsolutePath = $dataOutputDir . '/' . $enveloFilename;
                $this->saveSpreadsheet($spreadsheet, $enveloAbsolutePath);
                $outputFilesPaths[] = $enveloAbsolutePath;

                $zipFilename = 'envelo' . $enveloPackageNumber . '.zip';
                $this->zipModel->generate($outputFilesPaths, $zipOutputDir . '/' . $zipFilename);
                $zipFiles[] = $zipOutputDir . '/' . $zipFilename;

                $enveloPackageNumber++;

                $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);
                $enveloCounter = 0;
                $spreadsheetStartIndexFrom = 3;
                $outputFilesPaths = [];
            } else {
                $row = $this->dataRowPaymentRequest($object, $objectNumber);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);
            }

            $enveloCounter++;
            $index++;
        }

        $this->zipModel->download($zipFiles, 'envelo', false, 'envelo', true);
        $this->removeDirectory($enveloOutputDir); // removes generated envelo files
        die;
    }

    public function generateForSettlements($folderName, $objects)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container = $this->container;

        $kernelRootDir = $container->get('kernel')->getRootDir();
        $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);

        $enveloOutputDir = $kernelRootDir . '/../var/data/envelo-settlements/' . $folderName;
        if (!file_exists($enveloOutputDir)) {
            mkdir($enveloOutputDir, 0777, true);
        }

        $invoicesOutputDir = $enveloOutputDir . '/invoices';
        if (!file_exists($invoicesOutputDir)) {
            mkdir($invoicesOutputDir, 0777, true);
        }

        $zipOutputDir = $enveloOutputDir . '/zip';
        if (!file_exists($zipOutputDir)) {
            mkdir($zipOutputDir, 0777, true);
        }

        $dataOutputDir = $enveloOutputDir . '/data';
        if (!file_exists($dataOutputDir)) {
            mkdir($dataOutputDir, 0777, true);
        }


        $spreadsheetStartIndexFrom = 3;
        $outputFilesPaths = [];
        $enveloCounter = 1;
        $enveloPackageNumber = 1;
        $index = 0;
        $lastIterationIndex =  count($objects) - 1;
        $zipFiles = [];
        foreach ($objects as $object) {
            $objectNumber = $object->getNumber();
            if (!$objectNumber) {
                die('Dokument nie ma przypisanego numeru');
            }

            $objectNumber = str_replace('/', '-', $objectNumber);

            if ($object instanceof InvoiceSettlement) {
                $objectsOutputDir = $this->easyAdminModel->getEntityDirectoryByEntityName('InvoiceSettlementEnergy');
            } else {
                $objectsOutputDir = $this->easyAdminModel->getEntityDirectoryByEntityName('InvoiceEstimatedSettlementEnergy');
            }
            if (!file_exists($objectsOutputDir)) {
                mkdir($objectsOutputDir, 0777, true);
            }

            $filenamePrefix = '';
            $documentBankAccountChange = null;
            $documentBankAccountChangePath = null;
            if ($this->documentBankAccountChangeModel->canBeAppliedToDocument($object->getBadgeId(), $object->getNumber())) {
                $filenamePrefix = 'ZNR-';
                $objectNumber = $filenamePrefix . $objectNumber;
                /** @var DocumentBankAccountChange $documentBankAccountChange */
                $documentBankAccountChange = $this->documentBankAccountChangeModel->getRecordByBadgeId($object->getBadgeId());
                $documentBankAccountChangePath = $documentBankAccountChange->getFilePath();
            }

            $outputFilePath = $this->settlementModel->getDocumentPath($object, $objectsOutputDir) . '.pdf';

            if ($filenamePrefix) {
                $tmpOutputFilePath = $this->settlementModel->getDocumentPath($object, $objectsOutputDir, $filenamePrefix) . '.pdf';
                $tmpOutputForMerge = [];
                $tmpOutputForMerge[] = $outputFilePath;

                $tmpOutputForMerge = array_prepend($tmpOutputForMerge, $documentBankAccountChangePath);
                $this->pdfHelper->mergePdfFiles($tmpOutputForMerge, $tmpOutputFilePath);
                $outputFilePath = $tmpOutputFilePath;
            }
            $outputFilesPaths[] = $outputFilePath;

            if ($enveloCounter == self::MAX_SETTLEMENT_FILES_IN_PACKAGE || $index == $lastIterationIndex) { // Save and reset data
                $row = $this->dataRowSettlement($object, $objectNumber);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);

                $enveloFilename = 'envelo_' . $enveloPackageNumber . '.xls';
                $enveloAbsolutePath = $dataOutputDir . '/' . $enveloFilename;
                $this->saveSpreadsheet($spreadsheet, $enveloAbsolutePath);
                $outputFilesPaths[] = $enveloAbsolutePath;

                $zipFilename = 'envelo' . $enveloPackageNumber . '.zip';
                $this->zipModel->generate($outputFilesPaths, $zipOutputDir . '/' . $zipFilename);
                $zipFiles[] = $zipOutputDir . '/' . $zipFilename;

                $enveloPackageNumber++;

                $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);
                $enveloCounter = 0;
                $spreadsheetStartIndexFrom = 3;
                $outputFilesPaths = [];
            } else {
                $row = $this->dataRowSettlement($object, $objectNumber);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);
            }

            $enveloCounter++;
            $index++;
        }

        $this->zipModel->download($zipFiles, 'envelo', false, 'envelo', true);
        $this->removeDirectory($enveloOutputDir); // removes generated envelo files
        die;
    }

    public function generateForDebitNotes($folderName, $objects)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container = $this->container;

        $kernelRootDir = $container->get('kernel')->getRootDir();
        $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);

        $enveloOutputDir = $kernelRootDir . '/../var/data/envelo-debit-notes/' . $folderName;
        if (!file_exists($enveloOutputDir)) {
            mkdir($enveloOutputDir, 0777, true);
        }

        $invoicesOutputDir = $enveloOutputDir . '/invoices';
        if (!file_exists($invoicesOutputDir)) {
            mkdir($invoicesOutputDir, 0777, true);
        }

        $zipOutputDir = $enveloOutputDir . '/zip';
        if (!file_exists($zipOutputDir)) {
            mkdir($zipOutputDir, 0777, true);
        }

        $dataOutputDir = $enveloOutputDir . '/data';
        if (!file_exists($dataOutputDir)) {
            mkdir($dataOutputDir, 0777, true);
        }


        $spreadsheetStartIndexFrom = 3;
        $outputFilesPaths = [];
        $enveloCounter = 1;
        $enveloPackageNumber = 1;
        $index = 0;
        $lastIterationIndex =  count($objects) - 1;
        $zipFiles = [];
        /** @var DebitNote $object */
        foreach ($objects as $object) {
            $objectNumber = $object->getNumber();
            if (!$objectNumber) {
                die('Dokument nie ma przypisanego numeru');
            }

            $objectNumber = str_replace('/', '-', $objectNumber);
            $objectsOutputDir = $this->easyAdminModel->getEntityDirectoryByEntityName('DebitNote');
            if (!file_exists($objectsOutputDir)) {
                mkdir($objectsOutputDir, 0777, true);
            }

            $filenamePrefix = '';
            $documentBankAccountChange = null;
            $documentBankAccountChangePath = null;
            if ($this->documentBankAccountChangeModel->canBeAppliedToDocument($object->getBadgeId(), $object->getNumber())) {
                $filenamePrefix = 'ZNR-';
                $objectNumber = $filenamePrefix . $objectNumber;
                /** @var DocumentBankAccountChange $documentBankAccountChange */
                $documentBankAccountChange = $this->documentBankAccountChangeModel->getRecordByBadgeId($object->getBadgeId());
                $documentBankAccountChangePath = $documentBankAccountChange->getFilePath();
            }

            $outputFilePath = $this->debitNoteModel->getDocumentPath($object, $objectsOutputDir) . '.pdf';

            if ($filenamePrefix) {
                $tmpOutputFilePath = $this->debitNoteModel->getDocumentPath($object, $objectsOutputDir, $filenamePrefix) . '.pdf';
                $tmpOutputForMerge = [];
                $tmpOutputForMerge[] = $outputFilePath;

                $tmpOutputForMerge = array_prepend($tmpOutputForMerge, $documentBankAccountChangePath);
                $this->pdfHelper->mergePdfFiles($tmpOutputForMerge, $tmpOutputFilePath);
                $outputFilePath = $tmpOutputFilePath;
            }
            $outputFilesPaths[] = $outputFilePath;

            if ($enveloCounter == self::MAX_DEBIT_NOTE_FILES_IN_PACKAGE || $index == $lastIterationIndex) { // Save and reset data
                $row = $this->dataRowDebitNote($object, $objectNumber);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);

                $enveloFilename = 'envelo_' . $enveloPackageNumber . '.xls';
                $enveloAbsolutePath = $dataOutputDir . '/' . $enveloFilename;
                $this->saveSpreadsheet($spreadsheet, $enveloAbsolutePath);
                $outputFilesPaths[] = $enveloAbsolutePath;

                $zipFilename = 'envelo' . $enveloPackageNumber . '.zip';
                $this->zipModel->generate($outputFilesPaths, $zipOutputDir . '/' . $zipFilename);
                $zipFiles[] = $zipOutputDir . '/' . $zipFilename;

                $enveloPackageNumber++;

                $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);
                $enveloCounter = 0;
                $spreadsheetStartIndexFrom = 3;
                $outputFilesPaths = [];
            } else {
                $row = $this->dataRowDebitNote($object, $objectNumber);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);
            }

            $enveloCounter++;
            $index++;
        }

        $this->zipModel->download($zipFiles, 'envelo', false, 'envelo', true);
        $this->removeDirectory($enveloOutputDir); // removes generated envelo files
        die;
    }

    public function generateForDocumentsPackageToGenerate(DocumentPackageToGenerate $package, $folderName, $objects, $relativeDir = null)
    {
        if ($package->getType() == DocumentPackageToGenerateModel::TYPE_CUSTOM_DOCUMENT) {
            $em = $this->em;
            $em->getConnection()->getConfiguration()->setSQLLogger(null);
            $container = $this->container;

            $kernelRootDir = $container->get('kernel')->getRootDir();
            $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);

            $enveloOutputDir = $kernelRootDir . '/../var/data/envelo-document-package/' . $folderName;
            if (!file_exists($enveloOutputDir)) {
                mkdir($enveloOutputDir, 0777, true);
            }

            $invoicesOutputDir = $enveloOutputDir . '/documents';
            if (!file_exists($invoicesOutputDir)) {
                mkdir($invoicesOutputDir, 0777, true);
            }

            $zipOutputDir = $enveloOutputDir . '/zip';
            if (!file_exists($zipOutputDir)) {
                mkdir($zipOutputDir, 0777, true);
            }

            $dataOutputDir = $enveloOutputDir . '/data';
            if (!file_exists($dataOutputDir)) {
                mkdir($dataOutputDir, 0777, true);
            }

            $spreadsheetStartIndexFrom = 3;
            $outputFilesPaths = [];
            $enveloCounter = 1;
            $enveloPackageNumber = 1;
            $index = 0;
            $lastIterationIndex =  count($objects) - 1;
            $zipFiles = [];
            /** @var DocumentPackageToGenerateRecord $object */
            foreach ($objects as $object) {
                $objectNumber = $object->getId() . DocumentPackageToGenerateRecordModel::MERGED_FILENAME_POSTFIX;

                $outputFilePath = $this->documentPackageToGenerateRecordModel->getAbsolutePackageMergedFilePath($object->getPackage(), $object);
                $outputFilesPaths[] = $outputFilePath;

                if ($enveloCounter == self::MAX_DOCUMENT_PACKAGE_FILES_IN_PACKAGE || $index == $lastIterationIndex) { // Save and reset data
                    $row = $this->dataRowDocumentPackageToGenerateRecord($object, $objectNumber);
                    $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);

                    $enveloFilename = 'envelo_' . $enveloPackageNumber . '.xls';
                    $enveloAbsolutePath = $dataOutputDir . '/' . $enveloFilename;
                    $this->saveSpreadsheet($spreadsheet, $enveloAbsolutePath);
                    $outputFilesPaths[] = $enveloAbsolutePath;

                    $zipFilename = 'envelo' . $enveloPackageNumber . '.zip';
                    $this->zipModel->generate($outputFilesPaths, $zipOutputDir . '/' . $zipFilename);
                    $zipFiles[] = $zipOutputDir . '/' . $zipFilename;

                    $enveloPackageNumber++;

                    $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);
                    $enveloCounter = 0;
                    $spreadsheetStartIndexFrom = 3;
                    $outputFilesPaths = [];
                } else {
                    $row = $this->dataRowDocumentPackageToGenerateRecord($object, $objectNumber);
                    $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);
                }

                $enveloCounter++;
                $index++;
            }

            $this->zipModel->download($zipFiles, 'envelo', false, 'envelo', true);
            $this->removeDirectory($enveloOutputDir); // removes generated envelo files
            die;
        } elseif ($package->getType() == DocumentPackageToGenerateModel::TYPE_CORRECTION) {
            $em = $this->em;
            $em->getConnection()->getConfiguration()->setSQLLogger(null);
            $container = $this->container;

            $kernelRootDir = $container->get('kernel')->getRootDir();
            $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);

            $enveloOutputDir = $kernelRootDir . '/../var/data/envelo-document-package/' . $folderName;
            if (!file_exists($enveloOutputDir)) {
                mkdir($enveloOutputDir, 0777, true);
            }

            $invoicesOutputDir = $enveloOutputDir . '/documents';
            if (!file_exists($invoicesOutputDir)) {
                mkdir($invoicesOutputDir, 0777, true);
            }

            $zipOutputDir = $enveloOutputDir . '/zip';
            if (!file_exists($zipOutputDir)) {
                mkdir($zipOutputDir, 0777, true);
            }

            $dataOutputDir = $enveloOutputDir . '/data';
            if (!file_exists($dataOutputDir)) {
                mkdir($dataOutputDir, 0777, true);
            }

            // fetch invoices
            /** @var DocumentPackageToGenerateRecord $object */
            $invoices = [];
            foreach ($objects as $object) {
                $invoice = $this->documentPackageToGenerateRecordModel->fetchGeneratedRecordObject($package, $object);
                if (!$invoice) {
                    throw new \RuntimeException('Generated document not found');
                }
                $invoices[] = $invoice;
            }

            $clients = $this->mergeInvoicesWithClients($invoices, true);

            $spreadsheetStartIndexFrom = 3;
            $outputFilesPaths = [];
            $enveloCounter = 1;
            $enveloPackageNumber = 1;
            $index = 0;
            $lastIterationIndex =  count($clients) - 1;
            $zipFiles = [];

            foreach ($clients as $itemData) {
                $invoiceNumbers = $this->fetchInvoiceAiNumbersFromInvoices($itemData['invoices']);

                // adds oze if exist
                if ($itemData['bankAccountChange']) {
                    $invoiceNumbers = 'ZNR-' . $invoiceNumbers;
                }

                $outputFilePath = $invoicesOutputDir. '/' . $invoiceNumbers . '.pdf';
                $outputFilesPaths[] = $outputFilePath;
                $invoicesFromClientAbsolutePaths = $this->getInvoicesAbsoultePaths(
                    $kernelRootDir,
                    $itemData['invoices'],
                    $relativeDir
                );

                if ($itemData['bankAccountChange']) {
                    $invoicesFromClientAbsolutePaths = array_prepend($invoicesFromClientAbsolutePaths, $itemData['bankAccountChange']);
                }

                $this->pdfHelper->mergePdfFiles($invoicesFromClientAbsolutePaths, $outputFilePath);

                if ($enveloCounter == self::MAX_DOCUMENT_PACKAGE_TYPE_CORRECTION_FILES_IN_PACKAGE || $index == $lastIterationIndex) { // Save and reset data
                    $row = $this->dataRow($itemData['invoices'][0], $invoiceNumbers);
                    $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);

                    $enveloFilename = 'envelo_' . $enveloPackageNumber . '.xls';
                    $enveloAbsolutePath = $dataOutputDir . '/' . $enveloFilename;
                    $this->saveSpreadsheet($spreadsheet, $enveloAbsolutePath);
                    $outputFilesPaths[] = $enveloAbsolutePath;

                    $zipFilename = 'envelo' . $enveloPackageNumber . '.zip';
                    $this->zipModel->generate($outputFilesPaths, $zipOutputDir . '/' . $zipFilename);
                    $zipFiles[] = $zipOutputDir . '/' . $zipFilename;

                    $enveloPackageNumber++;

                    $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);
                    $enveloCounter = 0;
                    $spreadsheetStartIndexFrom = 3;
                    $outputFilesPaths = [];
                } else {
                    $row = $this->dataRow($itemData['invoices'][0], $invoiceNumbers);
                    $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);
                }

                $enveloCounter++;
                $index++;
            }

            $this->zipModel->download($zipFiles, 'envelo', false, 'envelo', true);
            $this->removeDirectory($enveloOutputDir); // removes generated envelo files
            die;
        }
    }


    public function generateForInvoiceCollective(InvoiceCollective $invoice, $folderName = 'invoice-collective')
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container = $this->container;

        $kernelRootDir = $container->get('kernel')->getRootDir();
        $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);

        $enveloOutputDir = $kernelRootDir . '/../var/data/envelo-settlements/' . $folderName;
        if (!file_exists($enveloOutputDir)) {
            mkdir($enveloOutputDir, 0777, true);
        }

        $invoicesOutputDir = $enveloOutputDir . '/invoices';
        if (!file_exists($invoicesOutputDir)) {
            mkdir($invoicesOutputDir, 0777, true);
        }

        $zipOutputDir = $enveloOutputDir . '/zip';
        if (!file_exists($zipOutputDir)) {
            mkdir($zipOutputDir, 0777, true);
        }

        $dataOutputDir = $enveloOutputDir . '/data';
        if (!file_exists($dataOutputDir)) {
            mkdir($dataOutputDir, 0777, true);
        }

        $objects[] = $invoice;

        $spreadsheetStartIndexFrom = 3;
        $outputFilesPaths = [];
        $enveloCounter = 1;
        $enveloPackageNumber = 1;
        $index = 0;
        $lastIterationIndex =  count($objects) - 1;
        $zipFiles = [];
        foreach ($objects as $object) {
            $objectNumber = $object->getNumber();
            if (!$objectNumber) {
                die('Dokument nie ma przypisanego numeru');
            }

            $objectNumber = str_replace('/', '-', $objectNumber);

            $outputFilePath = $this->documentPathReader->readByEntityName($object, 'InvoiceCollective', 'pdf');
            $outputFilesPaths[] = $outputFilePath;

            if ($enveloCounter == self::MAX_SETTLEMENT_FILES_IN_PACKAGE || $index == $lastIterationIndex) { // Save and reset data
                $row = $this->dataRowCollective($object, $objectNumber);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);

                $enveloFilename = 'envelo_' . $enveloPackageNumber . '.xls';
                $enveloAbsolutePath = $dataOutputDir . '/' . $enveloFilename;
                $this->saveSpreadsheet($spreadsheet, $enveloAbsolutePath);
                $outputFilesPaths[] = $enveloAbsolutePath;

                $zipFilename = 'envelo' . $enveloPackageNumber . '.zip';
                $this->zipModel->generate($outputFilesPaths, $zipOutputDir . '/' . $zipFilename);
                $zipFiles[] = $zipOutputDir . '/' . $zipFilename;

                $enveloPackageNumber++;

                $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);
                $enveloCounter = 0;
                $spreadsheetStartIndexFrom = 3;
                $outputFilesPaths = [];
            } else {
                $row = $this->dataRowCollective($object, $objectNumber);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);
            }

            $enveloCounter++;
            $index++;
        }

        $this->zipModel->download($zipFiles, 'envelo', false, 'envelo', true);
        $this->removeDirectory($enveloOutputDir); // removes generated envelo files
        die;
    }

    /**
     * Remove the directory and its content (all files and subdirectories).
     * @param string $dir the directory name
     */
    private function removeDirectory($dir) {
        foreach (glob($dir) as $file) {
            if (is_dir($file)) {
                $this->removeDirectory("$file/*");
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }

    private function mergeInvoicesWithClients($invoices, $omitOze = false)
    {
        $clients = [];

        /** @var \GCRM\CRMBundle\Entity\Settings\System $ozeObj */
        $ozeObj = $this->systemSettings->getRecord('certificate_oze');
        $ozePath = null;
        if ($ozeObj && $ozeObj->getFilePath()) {
            $ozePath = $this->systemSettings->getAbsoluteFilePath($ozeObj->getFilePath());
        }

        /** @var InvoiceInterface $invoice */
        foreach ($invoices as $invoice) {
            $contractNumber = $invoice->getContractNumber();
            if (!$contractNumber) {
                die('Na fakturze nr: ' . $invoice->getNumber() . ' brakuje numeru umowy.');
            }

            // check for OZE
            // for those contracts that dont have before invoicing period (new ones)
            $contract = $this->em->getRepository('GCRMCRMBundle:ContractGas')->findOneBy(['contractNumber' => $contractNumber]);
            if (!$contract) {
                $contract = $this->em->getRepository('GCRMCRMBundle:ContractEnergy')->findOneBy(['contractNumber' => $contractNumber]);
            }
            if (!$contract) {
                die('Nie można powiązać faktury nr: ' . $invoice->getNumber() . ' z umową. Umowa o numerze ' . $contractNumber . ' nie istnieje.');
            }

            $isOze = false;
            if (!$omitOze) {
                $isOze = !$contract->getBeforeInvoicingPeriod() && $contract->getType() == 'ENERGY' ? true : false;
            }

            // sent only to clients that already had invoices (with old number)
            $documentBankAccountChange = null;
            $documentBankAccountChangePath = null;
            if ($this->documentBankAccountChangeModel->canBeAppliedToDocument($invoice->getBadgeId(), $invoice->getNumber())) {
                /** @var DocumentBankAccountChange $documentBankAccountChange */
                $documentBankAccountChange = $this->documentBankAccountChangeModel->getRecordByBadgeId($invoice->getBadgeId());
                $documentBankAccountChangePath = $documentBankAccountChange->getFilePath();
            }

            /** @var Client $client */
            $client = $invoice->getClient();
            if (!key_exists($client->getAccountNumberIdentifier()->getNumber(), $clients)) {
                $clients[$client->getAccountNumberIdentifier()->getNumber()] = [
                    'client' => $client,
                    'invoices' => [],
                    'oze' => $isOze ? $ozePath : null,
                    'bankAccountChange' => $documentBankAccountChangePath,
                ];
            }
            $clients[$client->getAccountNumberIdentifier()->getNumber()]['invoices'][] = $invoice;
        }

        return $clients;
    }

    private function dataRow($tmpInvoice, $invoiceNumbers)
    {
        /** @var InvoiceInterface $tmpInvoice */
        $fullName = $tmpInvoice->getClientFullName();
        $splittedFullName = explode(' ', $fullName);
        $surnamePart1 = isset($splittedFullName[1]) ? $splittedFullName[1] : '';
        $surnamePart2 = isset($splittedFullName[2]) ? ' ' . $splittedFullName[2] : '';
        $surnamePart3 = isset($splittedFullName[3]) ? ' ' . $splittedFullName[3] : '';

        $name = $splittedFullName[0];
        $surname = $surnamePart1 . $surnamePart2 . $surnamePart3;

        $row = [
            0 => 'Sz.P.',
            1 => '',
            2 => $tmpInvoice->getPayerCompanyName() ? '' : $name,
            3 => $tmpInvoice->getPayerCompanyName() ? '' : $surname,
            4 => $tmpInvoice->getPayerCompanyName() ?: '',
            5 => $tmpInvoice->getPayerStreet() ?: $tmpInvoice->getClientStreet(),
            6 => $tmpInvoice->getPayerHouseNr() ?: $tmpInvoice->getClientHouseNr(),
            7 => $tmpInvoice->getPayerApartmentNr() ?: $tmpInvoice->getClientApartmentNr(),
            8 => $tmpInvoice->getPayerZipCode() ?: $tmpInvoice->getClientZipCode(),
            9 => $tmpInvoice->getPayerCity() ?: $tmpInvoice->getClientCity(),
            10 => 'Polska',
            11 => '1',
            12 => 'N',
            13 => 'N',
            14 => 'N',
            15 => '1',
            16 => '',
            17 => $invoiceNumbers . '.pdf',
            18 => 'N',
            19 => 'D',
            20 => 'Y',
            21 => 'Y',
        ];

        return $row;
    }

    private function dataRowDebitNote(DebitNote $debitNote, $number)
    {
        $row = [
            0 => 'Sz.P.',
            1 => '',
            2 => $debitNote->getClientName(),
            3 => $debitNote->getClientSurname(),
            4 => '',
            5 => $debitNote->getClientStreet(),
            6 => $debitNote->getClientHouseNr(),
            7 => $debitNote->getClientApartmentNr(),
            8 => $debitNote->getClientZipCode(),
            9 => $debitNote->getClientCity(),
            10 => 'Polska',
            11 => '1',
            12 => 'N',
            13 => 'N',
            14 => 'N',
            15 => '1',
            16 => '',
            17 => $number . '.pdf',
            18 => 'N',
            19 => 'D',
            20 => 'Y',
            21 => 'Y',
        ];

        return $row;
    }

    private function dataRowDocumentPackageToGenerateRecord(DocumentPackageToGenerateRecord $documentPackageToGenerateRecord, $number)
    {
        /** @var Client $client */
        $client = $documentPackageToGenerateRecord->getClient();

        $row = [
            0 => 'Sz.P.',
            1 => '',
            2 => $client->getName(),
            3 => $client->getSurname(),
            4 => '',
            5 => $client->getToCorrespondenceStreet(),
            6 => $client->getToCorrespondenceHouseNr(),
            7 => $client->getToCorrespondenceApartmentNr(),
            8 => $client->getToCorrespondenceZipCode(),
            9 => $client->getToCorrespondenceCity(),
            10 => 'Polska',
            11 => '1',
            12 => 'N',
            13 => 'N',
            14 => 'N',
            15 => '1',
            16 => '',
            17 => $number,
            18 => 'N',
            19 => 'D',
            20 => 'Y',
            21 => 'Y',
        ];

        return $row;
    }

    private function dataRowSettlement($tmpInvoice, $invoiceNumber)
    {
        /** @var InvoiceInterface $tmpInvoice */
        $fullName = $tmpInvoice->getClientFullName();
        $splittedFullName = explode(' ', $fullName);
        $surnamePart1 = isset($splittedFullName[1]) ? $splittedFullName[1] : '';
        $surnamePart2 = isset($splittedFullName[2]) ? ' ' . $splittedFullName[2] : '';
        $surnamePart3 = isset($splittedFullName[3]) ? ' ' . $splittedFullName[3] : '';

        $name = $splittedFullName[0];
        $surname = $surnamePart1 . $surnamePart2 . $surnamePart3;

        $row = [
            0 => 'Sz.P.',
            1 => '',
            2 => $tmpInvoice->getPayerCompanyName() ? '' : $name,
            3 => $tmpInvoice->getPayerCompanyName() ? '' : $surname,
            4 => $tmpInvoice->getPayerCompanyName() ?: '',
            5 => $tmpInvoice->getPayerStreet() ?: $tmpInvoice->getClientStreet(),
            6 => $tmpInvoice->getPayerHouseNr() ?: $tmpInvoice->getClientHouseNr(),
            7 => $tmpInvoice->getPayerApartmentNr() ?: $tmpInvoice->getClientApartmentNr(),
            8 => $tmpInvoice->getPayerZipCode() ?: $tmpInvoice->getClientZipCode(),
            9 => $tmpInvoice->getPayerCity() ?: $tmpInvoice->getClientCity(),
            10 => 'Polska',
            11 => '1',
            12 => 'N',
            13 => 'N',
            14 => 'N',
            15 => '1',
            16 => '',
            17 => $invoiceNumber . '.pdf',
            18 => 'N',
            19 => 'D',
            20 => 'Y',
            21 => 'Y',
            22 => $tmpInvoice->getIsInInvoiceCollective() ? 'TAK' : 'NIE',
        ];

        return $row;
    }

    private function dataRowCollective($tmpInvoice, $invoiceNumber)
    {
        $fullName = $tmpInvoice->getClientFullName();
        $splittedFullName = explode(' ', $fullName);
        $surnamePart1 = isset($splittedFullName[1]) ? $splittedFullName[1] : '';
        $surnamePart2 = isset($splittedFullName[2]) ? ' ' . $splittedFullName[2] : '';
        $surnamePart3 = isset($splittedFullName[3]) ? ' ' . $splittedFullName[3] : '';

        $name = $splittedFullName[0];
        $surname = $surnamePart1 . $surnamePart2 . $surnamePart3;

        $row = [
            0 => 'Sz.P.',
            1 => '',
            2 => $tmpInvoice->getPayerCompanyName() ? '' : $name,
            3 => $tmpInvoice->getPayerCompanyName() ? '' : $surname,
            4 => $tmpInvoice->getPayerCompanyName() ?: '',
            5 => $tmpInvoice->getPayerStreet() ?: $tmpInvoice->getClientStreet(),
            6 => $tmpInvoice->getPayerHouseNr() ?: $tmpInvoice->getClientHouseNr(),
            7 => $tmpInvoice->getPayerApartmentNr() ?: $tmpInvoice->getClientApartmentNr(),
            8 => $tmpInvoice->getPayerZipCode() ?: $tmpInvoice->getClientZipCode(),
            9 => $tmpInvoice->getPayerCity() ?: $tmpInvoice->getClientCity(),
            10 => 'Polska',
            11 => '1',
            12 => 'N',
            13 => 'N',
            14 => 'N',
            15 => '1',
            16 => '',
            17 => $invoiceNumber . '.pdf',
            18 => 'N',
            19 => 'D',
            20 => 'Y',
            21 => 'Y',
        ];

        return $row;
    }

    private function dataRowPaymentRequest(\Wecoders\EnergyBundle\Entity\PaymentRequest $object, $objectNumber)
    {
        $row = [
            0 => 'Sz.P.',
            1 => '',
            2 => $object->getClientName(),
            3 => $object->getClientSurname(),
            4 => '',
            5 => $object->getClientStreet(),
            6 => $object->getClientHouseNr(),
            7 => $object->getClientApartmentNr(),
            8 => $object->getClientZipCode(),
            9 => $object->getClientCity(),
            10 => 'Polska',
            11 => '1',
            12 => 'N',
            13 => 'N',
            14 => 'N',
            15 => '1',
            16 => '',
            17 => $objectNumber . '.pdf',
            18 => 'N',
            19 => 'D',
            20 => 'Y',
            21 => 'Y',
        ];

        return $row;
    }

    private function fetchInvoiceAiNumbersFromInvoices($invoices)
    {
        $invoiceNumbers = [];
        foreach ($invoices as $invoice) {
            $pieces = explode('/', $invoice->getNumber());
            $invoiceNumbers[] = $pieces[0];
        }

        return implode('-', $invoiceNumbers);
    }

    private function getInvoicesAbsoultePaths($kernelRootDir, $invoices, $relativeDir)
    {
        $files = [];

        /** @var InvoiceInterface, InvoicePathInterface $invoice */
        foreach ($invoices as $invoice) {
            $fullInvoicePath = $this->invoiceModel->fullInvoicePath($kernelRootDir, $invoice, $relativeDir);
            $files[] = $fullInvoicePath . '.pdf';
        }
        return $files;
    }

    private function getNewSpreadsheet($kernelRootDir)
    {
        $kernelRootDir = $this->container->get('kernel')->getRootDir();
        return $this->getSpreadsheet($kernelRootDir . '/../var/data/envelo-template.xls');
    }

    protected function getDataRows($file, $firstDataRowIndex, $highestColumn)
    {
        $reader = new Xls();
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn++;

        $rows = [];

        for ($row = $firstDataRowIndex; $row <= $highestRow; ++$row) {
            $rows[$row] = [];
            for ($col = 'A'; $col != $highestColumn; ++$col) {
                $rows[$row][] = $worksheet->getCell($col . $row)->getFormattedValue();
            }
        }

        return $rows;
    }

    protected function getSpreadsheet($file)
    {
        $reader = new Xls();
        return $spreadsheet = $reader->load($file);
    }

    private function addDataRowToSpreadsheet(&$spreadsheet, &$row, &$index)
    {
        $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $row[0]);
        $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $row[1]);
        $spreadsheet->getActiveSheet()->setCellValue('C' . $index, $row[2]);
        $spreadsheet->getActiveSheet()->setCellValue('D' . $index, $row[3]);
        $spreadsheet->getActiveSheet()->setCellValue('E' . $index, $row[4]);
        $spreadsheet->getActiveSheet()->setCellValue('F' . $index, $row[5]);
        $spreadsheet->getActiveSheet()->setCellValue('G' . $index, $row[6]);
        $spreadsheet->getActiveSheet()->setCellValue('H' . $index, $row[7]);
        $spreadsheet->getActiveSheet()->setCellValue('I' . $index, $row[8]);
        $spreadsheet->getActiveSheet()->setCellValue('J' . $index, $row[9]);
        $spreadsheet->getActiveSheet()->setCellValue('K' . $index, $row[10]);
        $spreadsheet->getActiveSheet()->setCellValue('L' . $index, $row[11]);
        $spreadsheet->getActiveSheet()->setCellValue('M' . $index, $row[12]);
        $spreadsheet->getActiveSheet()->setCellValue('N' . $index, $row[13]);
        $spreadsheet->getActiveSheet()->setCellValue('O' . $index, $row[14]);
        $spreadsheet->getActiveSheet()->setCellValue('P' . $index, $row[15]);
        $spreadsheet->getActiveSheet()->setCellValue('Q' . $index, $row[16]);
        $spreadsheet->getActiveSheet()->setCellValue('R' . $index, $row[17]);
        $spreadsheet->getActiveSheet()->setCellValue('S' . $index, $row[18]);
        $spreadsheet->getActiveSheet()->setCellValue('T' . $index, $row[19]);
        $spreadsheet->getActiveSheet()->setCellValue('U' . $index, $row[20]);
        $spreadsheet->getActiveSheet()->setCellValue('V' . $index, $row[21]);

        if (isset($row[22])) {
            $spreadsheet->getActiveSheet()->setCellValue('AA' . $index, $row[22]);
        }

        $index++;
    }

    private function saveSpreadsheet($spreadsheet, $outputFilePath)
    {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header_remove();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Envelo.xls"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($outputFilePath);
    }

}