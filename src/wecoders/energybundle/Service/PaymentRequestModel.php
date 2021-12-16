<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\Settings\System;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\PaymentRequest;
use Wecoders\EnergyBundle\Entity\PaymentRequestAndDocument;
use Wecoders\InvoiceBundle\Service\InvoiceData;

class PaymentRequestModel extends DocumentModel
{
    const ENTITY = 'WecodersEnergyBundle:PaymentRequest';

    private $invoiceData;

    public function __construct(
        EntityManager $em,
        ContainerInterface $container,
        InvoiceData $invoiceData,
        EasyAdminModel $easyAdminModel,
        System $systemSettings
    )
    {
        $this->invoiceData = $invoiceData;

        parent::__construct($container, $systemSettings, $em, $easyAdminModel);
    }

    public function getDocumentPath(PaymentRequest $paymentRequest, $absouluteDirPath, $filenamePrefix = '')
    {
        $dirPath = $this->generateDocumentDir($paymentRequest, $absouluteDirPath);

        return $dirPath . '/' . $filenamePrefix . $paymentRequest->getId();
    }

    public function generateDocumentDir(PaymentRequest $paymentRequest, $absouluteDirPath)
    {
        $createdDate = $paymentRequest->getCreatedDate();
        $dirPath = $absouluteDirPath . '/' . $createdDate->format('Y') . '/' . $createdDate->format('m');
        if (file_exists(!$dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        return $dirPath;
    }

    public function generatePaymentRequestDocument(PaymentRequest $paymentRequest, $documentPath, $templateAbsolutePath, $logoAbsolutePath, $documentType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $logoAbsolutePath);

        $city = $paymentRequest->getClientCity();
        $street = $paymentRequest->getClientStreet();
        $houseNr = $paymentRequest->getClientHouseNr();
        $apartmentNr = $paymentRequest->getClientApartmentNr();

        $addressWithPrefix = $this->manageAddress($city, $street, $houseNr, $apartmentNr, true);

        $template->setValue('createdDate', $paymentRequest->getCreatedDate()->format('d-m-Y'));
        $template->setValue('contractNumber', $paymentRequest->getContractNumber());
        $template->setValue('summary', number_format($paymentRequest->getSummaryGrossValue(), 2, ',', ''));
        $template->setValue('summaryInWords', $this->invoiceData->getPriceInWords($paymentRequest->getSummaryGrossValue()));
        $template->setValue('clientFullName', $paymentRequest->getClientName() . ' ' . $paymentRequest->getClientSurname());
        $template->setValue('clientAddressWithPrefix', $addressWithPrefix);
        $template->setValue('clientZipCode', $paymentRequest->getClientZipCode());
        $template->setValue('clientCity', $paymentRequest->getClientCity());
        $template->setValue('clientAccountNumber', $paymentRequest->getClientAccountNumber());
        $template->setValue('energyTypeInWords', $documentType == 'ENERGY' ? 'prądu' : 'gazu');

        $tableWidth = 9100;

        $headings = [
            [
                'text' => 'Okres rozliczeniowy',
            ],
            [
                'text' => 'Dni po terminie płatności',
            ],
            [
                'text' => 'Numer dokumentu',
            ],
            [
                'text' => 'Do zapłaty',
            ],
        ];

        $rows = [];
        /** @var PaymentRequestAndDocument $document */
        foreach ($paymentRequest->getPaymentRequestAndDocuments() as $document) {
            $billingPeriod = '';
            if ($document->getBillingPeriodFrom() && $document->getBillingPeriodTo()) {
                $billingPeriod = $document->getBillingPeriodFrom()->format('d-m-Y') . ' - ' . $document->getBillingPeriodTo()->format('d-m-Y');
            }

            $rows[] = [
                [
                    'text' => $billingPeriod,
                ],
                [
                    'text' => $document->getDaysOverdue(),
                ],
                [
                    'text' => $document->getDocumentNumber(),
                ],
                [
                    'text' => number_format($document->getToPay(), 2, ',', ''),
                ],
            ];
        }

        $this->createTable($template, 'table', $tableWidth, $headings, $rows, 'center');

        $template->saveAs($documentPath . '.docx');

        shell_exec('unoconv -f pdf ' . $documentPath . '.docx');

    }

    public function createTable(TemplateProcessor &$template, $variableName, $boxSize, $headings, $rows, $pStyleAlign = 'left')
    {
        $cellsCount = count($headings) ? count($headings) : count($rows);
        foreach ($headings as $cell) {
            if (isset($cell['width'])) {
                $cellsCount--;
                $boxSize = $boxSize - $cell['width'];
            }
        }
        $cellCalculatedSize = $boxSize / $cellsCount;



        $paramsTable = array(
            'tableAlign' => 'center',
        );

        $cellStyle = [
            'valign' => 'center',
            'borderBottomColor' =>'black',
            'borderBottomSize' => 1,
        ];

        $headingsStyle = [
            'name' => 'Carlito',
            'size' => '10',
            'bold' => false,
        ];

        $fontStyle = [
            'name' => 'Carlito',
            'size' => '10'
        ];
        $pStyle = [
            'align' => $pStyleAlign,
            'spaceBefore' => 0,
            'spaceAfter' => 0,
            'lineHeight' => 1,
        ];

        $table = new Table($paramsTable);
        $table->addRow();

        foreach ($headings as $cell) {
            $cellSize = isset($cell['width']) ? $cell['width'] : $cellCalculatedSize;

            $tmpCellStyle = $cellStyle;
            $tmpCellStyle = isset($cell['cellStyle']) ? (
            isset($cell['cellStyle']['append']) ? array_merge($cellStyle, $cell['cellStyle']['append']) : $cell['cellStyle']['new']
            ) : $tmpCellStyle;

            $tmpPstyle = $pStyle;
            $tmpPstyle = isset($cell['pStyle']) ? (
            isset($cell['pStyle']['append']) ? array_merge($pStyle, $cell['pStyle']['append']) : $cell['pStyle']['new']
            ) : $tmpPstyle;

            $tmpFontStyle = $headingsStyle;
            $tmpFontStyle = isset($cell['fontStyle']) ? (
            isset($cell['fontStyle']['append']) ? array_merge($headingsStyle, $cell['fontStyle']['append']) : $cell['fontStyle']['new']
            ) : $tmpFontStyle;

            $table->addCell($cellSize, $tmpCellStyle)->addText($cell['text'], $tmpFontStyle, $tmpPstyle);
        }

        foreach ($rows as $row) {
            $table->addRow();
            $index = 0;
            foreach ($row as $cell) {
                $tmpCellStyle = $cellStyle;
                $tmpCellStyle = isset($cell['cellStyle']) ? (
                isset($cell['cellStyle']['append']) ? array_merge($cellStyle, $cell['cellStyle']['append']) : $cell['cellStyle']['new']
                ) : $tmpCellStyle;

                $tmpPstyle = $pStyle;
                $tmpPstyle = isset($cell['pStyle']) ? (
                isset($cell['pStyle']['append']) ? array_merge($pStyle, $cell['pStyle']['append']) : $cell['pStyle']['new']
                ) : $tmpPstyle;

                $tmpFontStyle = $fontStyle;
                $tmpFontStyle = isset($cell['fontStyle']) ? (
                isset($cell['fontStyle']['append']) ? array_merge($fontStyle, $cell['fontStyle']['append']) : $cell['fontStyle']['new']
                ) : $tmpFontStyle;

                $table->addCell(null, $tmpCellStyle)->addText($cell['text'], $tmpFontStyle, $tmpPstyle);
                $index++;
            }
        }
        $template->setComplexBlock($variableName, $table);
    }

    public function applyLogo(TemplateProcessor $template, $logoAbsolutePath)
    {
        $template->setImageValue('logo', [
            'path' => $logoAbsolutePath,
            'width' => 250,
            'height' => 200,
        ]);
    }

    public function getRecordsByClient(Client $client)
    {
        $result = $this->em->getRepository(self::ENTITY)->findBy(['client' => $client]);

        if ($result) {
            $absoluteDirPath = $this->easyAdminModel->getEntityDirectoryByEntityName('PaymentRequest');

            /** @var PaymentRequest $paymentRequest */
            foreach ($result as $paymentRequest) {
                if (file_exists($this->getDocumentPath($paymentRequest, $absoluteDirPath)) . '.pdf') {
                    $paymentRequest->setIsGeneratedFileExist(true);
                }
            }
        }

        return $result;
    }

    public function getRecords()
    {
        return $this->em->getRepository(self::ENTITY)->findAll();
    }

    public function getRecordsNotPaid()
    {
        return $this->em->getRepository(self::ENTITY)->findBy(['isPaid' => false]);
    }
}