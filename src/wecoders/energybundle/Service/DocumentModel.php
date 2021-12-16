<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\OptionArrayInterface;
use GCRM\CRMBundle\Service\Settings\System;
use PhpOffice\PhpWord\TemplateProcessor;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\Brand;
use PhpOffice\PhpWord\Element\Table;

class DocumentModel implements OptionArrayInterface
{
    const DOCUMENT_INVOICE = 1;
    const DOCUMENT_INVOICE_CORRECTION = 2;

    const DOCUMENT_INVOICE_PROFORMA = 3;
    const DOCUMENT_INVOICE_PROFORMA_CORRECTION = 4;

    const DOCUMENT_INVOICE_SETTLEMENT = 5;
    const DOCUMENT_INVOICE_SETTLEMENT_CORRECTION = 6;

    const DOCUMENT_INVOICE_ESTIMATED_SETTLEMENT = 7;
    const DOCUMENT_INVOICE_ESTIMATED_SETTLEMENT_CORRECTION = 8;

    const DOCUMENT_INVOICE_COLLECTIVE = 9;
    const DOCUMENT_INVOICE_COLLECTIVE_CORRECTION = 10;

    const DOCUMENT_DEBIT_NOTE = 11;
    const DOCUMENT_DEBIT_NOTE_CORRECTION = 12;

    const DOCUMENT_PAYMENT_REQUEST = 13;
    const DOCUMENT_PAYMENT_REQUEST_CORRECTION = 14;

    const DOCUMENT_CUSTOM_DOCUMENT_TEMPLATE = 15;

    protected $kernelRootDir;

    protected $container;

    protected $systemSettings;

    /** @var  EasyAdminModel */
    protected $easyAdminModel;

    protected $em;

    public static function getOptionArray()
    {
        return [
            self::DOCUMENT_INVOICE => 'Faktura',
            self::DOCUMENT_INVOICE_CORRECTION => 'Faktura korekta',
            self::DOCUMENT_INVOICE_PROFORMA => 'Faktura proforma',
            self::DOCUMENT_INVOICE_PROFORMA_CORRECTION => 'Faktura proforma korekta',
            self::DOCUMENT_INVOICE_SETTLEMENT => 'Faktura rozliczeniowa',
            self::DOCUMENT_INVOICE_SETTLEMENT_CORRECTION => 'Faktura rozliczeniowa korekta',
            self::DOCUMENT_INVOICE_ESTIMATED_SETTLEMENT => 'Faktura rozliczeniowa szacunkowa',
            self::DOCUMENT_INVOICE_ESTIMATED_SETTLEMENT_CORRECTION => 'Faktura rozliczeniowa szacunkowa korekta',
            self::DOCUMENT_INVOICE_COLLECTIVE => 'Faktura zbiorcza',
//            self::DOCUMENT_INVOICE_COLLECTIVE_CORRECTION => 'Faktura zbiorcza korekta',
            self::DOCUMENT_DEBIT_NOTE => 'Nota obciążeniowa',
//            self::DOCUMENT_DEBIT_NOTE_CORRECTION => 'Nota obciążeniowa korekta',
            self::DOCUMENT_PAYMENT_REQUEST => 'Wezwanie do zapłaty',
//            self::DOCUMENT_PAYMENT_REQUEST_CORRECTION => 'Wezwanie do zapłaty korekta',
        ];
    }

    public static function getOptionByValue($value)
    {
        $options = self::getOptionArray();
        foreach ($options as $key => $option) {
            if ($key == $value) {
                return $option;
            }
        }

        return null;
    }

    public function getMappedOptionByValue($value)
    {
        $options = self::getOptionArray();

        foreach ($options as $key => $option) {
            if ($key == $value) {
                $mapList = $this->container->getParameter('map.document_const_id_with_easyadmin_entity');
                foreach ($mapList as $id => $entity) {
                    if ($id == $value) {
                        return $entity;
                    }
                }
            }
        }

        return null;
    }

    public function getMappedIdByOption($option)
    {
        $mapList = $this->container->getParameter('map.document_const_id_with_easyadmin_entity');

        foreach ($mapList as $id => $entity) {
            if ($entity == $option) {
                return $id;
            }
        }

        return null;
    }



    public function __construct(
        ContainerInterface $container,
        System $systemSettings,
        EntityManagerInterface $em,
        EasyAdminModel $easyAdminModel
    )
    {
        $this->container = $container;
        $this->kernelRootDir = $container->get('kernel')->getRootDir();
        $this->systemSettings = $systemSettings;
        $this->em = $em;
        $this->easyAdminModel = $easyAdminModel;
    }

    public function getCorrectionEntity($documentEntity)
    {
        $entityClass = $this->easyAdminModel->getEntityConfigByEntityName($documentEntity);
        if (!$entityClass) {
            return null;
        }

        return $entityClass['cloneAsEntity'];
    }

    private function getLogoFilenameDefault()
    {
        return $this->systemSettings->getRecord(System::LOGO_DOCUMENT_DEFAULT)->getFilePath();
    }

    public function getLogoAbsolutePath($brand = null)
    {
        $result = null;

        if ($brand && $brand instanceof Brand) {
            $result = $this->getLogoAbsolutePathByBrand($brand);
        }

        // fall back to default if brand logo is not defined
        if (!$result) {
            $result = $this->getLogoAbsolutePathDefault();
        }

        if (!file_exists($result)) {
            throw new \Exception('Wystąpił błąd - plik nie został wygenerowany. Nie znaleziono pliku logo. Sprawdź czy został wgrany i spróbuj ponownie.');
        }

        return $result;
    }

    private function getLogoAbsolutePathByBrand(Brand $brand)
    {
        $filename = $brand->getFilePath();
        if (!$filename) {
            return null;
        }

        return $this->container->get('kernel')->getRootDir() . '/../web' . $this->container->getParameter('vich.path.relative.province') . '/' . $filename;
    }

    private function getLogoAbsolutePathDefault()
    {
        $filename = $this->getLogoFilenameDefault();
        if (!$filename) {
            return null;
        }

        return $this->container->get('kernel')->getRootDir() . '/../web' . $this->container->getParameter('vich.path.relative.system_settings') . '/' . $filename;
    }

    public function manageAddress($city = null, $street = null, $houseNr = null, $apartmentNr = null, $returnWithPrefix = false)
    {
        if ($street) {
            if ($apartmentNr) {
                $clientAddress = $street . ' ' . $houseNr . '/' . $apartmentNr;
            } else {
                $clientAddress = $street . ' ' . $houseNr;
            }
            $clientAddressWithPrefix = 'ul. ' . $clientAddress;
        } else { // if client does not have street address -> apply then city (as street)
            if ($apartmentNr) {
                $clientAddress = $city . ' ' . $houseNr . '/' . $apartmentNr;
            } else {
                $clientAddress = $city . ' ' . $houseNr;
            }
            $clientAddressWithPrefix = $clientAddress;
        }

        if ($returnWithPrefix) {
            return $clientAddressWithPrefix;
        }
        return $clientAddress;
    }

    public function applyBarcode(TemplateProcessor $template, $value)
    {
        $generatorPNG = new BarcodeGeneratorPNG();
        $barcodesDir = $this->kernelRootDir . '/../var/data/uploads/barcodes';
        if (!file_exists($barcodesDir)) {
            mkdir($barcodesDir, 0775, true);
        }

        $fullBarcodePath = $barcodesDir . '/' . $value . '.png';
        file_put_contents($fullBarcodePath,  $generatorPNG->getBarcode($value, $generatorPNG::TYPE_CODE_128));
        $template->setImageValue('barcode', [
            'path' => $fullBarcodePath,
            'width' => 250,
            'height' => 250
        ]);

        return $fullBarcodePath;
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
            'size' => '9',
            'bold' => true,
        ];

        $fontStyle = [
            'name' => 'Carlito',
            'size' => '8'
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

    public function fetchDocumentByType($type, $number)
    {
        $documentEntity = self::getMappedOptionByValue($type);
        if (!$documentEntity) {
            return null;
        }

        $entityClass = $this->easyAdminModel->getEntityClassByEntityName($documentEntity);
        if (!$entityClass) {
            return null;
        }

        return $this->em->getRepository($entityClass)->findOneBy(['number' => $number]);
    }
}