<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\OptionArrayInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\CustomDocumentTemplate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerateRecord;

class DocumentPackageToGenerateRecordModel implements OptionArrayInterface
{
    const STATUS_WAITING_TO_PROCESS = 1;
    const STATUS_IN_PROCESS = 2;
    const STATUS_WAITING_TO_GENERATE = 3;
    const STATUS_GENERATE = 4;
    const STATUS_COMPLETE = 5;
    const STATUS_GENERATE_ERROR = 101;
    const STATUS_PROCESS_ERROR = 201;
    const ENTITY = 'WecodersEnergyBundle:DocumentPackageToGenerateRecord';
    const MERGED_FILENAME_POSTFIX = '-m.pdf';

    private $em;
    private $container;
    private $documentModel;
    private $easyAdminModel;
    private $invoiceBundleInvoiceModel;
    private $invoiceModel;

    public static function getOptionArray()
    {
        return [
            self::STATUS_WAITING_TO_PROCESS => 'czeka do procesu',
            self::STATUS_IN_PROCESS => 'trwa przetwarzanie',
            self::STATUS_WAITING_TO_GENERATE => 'czeka do generowania',
            self::STATUS_GENERATE => 'trwa generowanie dokumentów',
            self::STATUS_COMPLETE => 'zakończono',
            self::STATUS_GENERATE_ERROR => 'błąd generowania',
            self::STATUS_PROCESS_ERROR => 'błąd procesowania',
        ];
    }

    public static function getOptionByValue($option)
    {
        $options = self::getOptionArray();
        if ($options) {
            foreach ($options as $key => $value) {
                if ($key == $option) {
                    return $value;
                }
            }
        }

        return null;
    }

    public function __construct(
        EntityManager $em,
        ContainerInterface $container,
        DocumentModel $documentModel,
        EasyAdminModel $easyAdminModel,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel,
        InvoiceModel $invoiceModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->documentModel = $documentModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->invoiceBundleInvoiceModel = $invoiceBundleInvoiceModel;
        $this->invoiceModel = $invoiceModel;
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }

    public function getAbsolutePackageDirPath(DocumentPackageToGenerate $package, DocumentPackageToGenerateRecord $record)
    {
        return $this->container->get('kernel')->getRootDir() . '/../' . DocumentPackageToGenerateModel::RELATIVE_DIR_PATH . '/' . $package->getId() . '/' . $record->getId();
    }

    public function getAbsolutePackageMergedFilePath(DocumentPackageToGenerate $package, DocumentPackageToGenerateRecord $record)
    {
        return $this->getAbsolutePackageDirPath($package, $record) . '/' . $record->getId() . self::MERGED_FILENAME_POSTFIX;
    }

    public function fetchRecordObject(DocumentPackageToGenerate $package, DocumentPackageToGenerateRecord $record)
    {
        $entityOption = $this->documentModel->getMappedOptionByValue($package->getDocumentEntity());
        $class = $this->easyAdminModel->getEntityClassByEntityName($entityOption);

        return $this->em->getRepository($class)->find($record->getDocumentId());
    }

    public function fetchGeneratedRecordObject(DocumentPackageToGenerate $package, DocumentPackageToGenerateRecord $record)
    {
        $entityOption = $this->documentModel->getMappedOptionByValue($package->getGeneratedDocumentEntity());
        $class = $this->easyAdminModel->getEntityClassByEntityName($entityOption);

        return $this->em->getRepository($class)->find($record->getGeneratedDocumentId());
    }
//
//    public function getCorrectionEntity(DocumentPackageToGenerate $package, DocumentPackageToGenerateRecord $record)
//    {
//        return $this->documentModel->getMappedOptionByValue($record->getGeneratedDocumentEntity());
//    }

    public function createCorrectionObject(DocumentPackageToGenerate $package, DocumentPackageToGenerateRecord $record)
    {
        return $this->invoiceModel->createCorrectionObject(
            $this->documentModel->getMappedOptionByValue($package->getDocumentEntity()),
            $record->getDocumentId(),
            false,
            false
        );
    }

    public function cloneRecordObject($record)
    {
        return clone $record;
    }

    public function getSingleRecordByStatus($status)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['status' => $status]);
    }

//    public function fetchRecordObject(DocumentPackageToGenerate $package)
//    {
//        $this->em->getConnection()->beginTransaction();
////            $type = $documentPackageToGenerate->getType();
//        $entity = $package->getDocumentEntity();
//        $entityOption = $this->documentModel->getMappedOptionByValue($entity);
//        $class = $this->easyAdminModel->getEntityClassByEntityName($entityOption);
//
//        $records = $package->getPackageRecords();
//        /** @var DocumentPackageToGenerateRecord $record */
//        foreach ($records as $record) {
//            $document = $this->em->getRepository($class)->find($record->getDocumentId());
//
//            $directoryRelative = $this->easyAdminModel->getEntityDirectoryRelativeByEntityName($entityOption);
//
//            $invoicePath = $this->invoiceBundleInvoiceModel->fullInvoicePath($this->container->get('kernel')->getRootDir(), $document, $directoryRelative);
//            if (file_exists($invoicePath . '.pdf')) {
//                unlink($invoicePath . '.pdf');
//            }
//            if (file_exists($invoicePath . '.docx')) {
//                unlink($invoicePath . '.docx');
//            }
//        }
//
//    }

}