<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\User;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\OptionArrayInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\CustomDocumentTemplate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerateRecord;

class DocumentPackageToGenerateModel implements OptionArrayInterface
{
    const STATUS_WAITING_TO_PROCESS = 1;
    const STATUS_IN_PROCESS = 2;
    const STATUS_WAITING_TO_GENERATE = 3;
    const STATUS_GENERATE = 4;
    const STATUS_COMPLETE = 5;
    const STATUS_GENERATE_ERROR = 101;
    const STATUS_PROCESS_ERROR = 201;

    const ENTITY = 'WecodersEnergyBundle:DocumentPackageToGenerate';
    const RELATIVE_DIR_PATH = 'var/data/document-package-to-generate';

    const TYPE_CORRECTION = 1;
    const TYPE_CUSTOM_DOCUMENT = 2;

    private $em;

    private $container;

    private $documentModel;

    private $easyAdminModel;

    private $invoiceBundleInvoiceModel;

    public function getAbsoluteDirPath()
    {
        return $this->container->get('kernel')->getRootDir() . '/../' . self::RELATIVE_DIR_PATH;
    }

    public function getAbsolutePackageDirPath(DocumentPackageToGenerate $package)
    {
        return $this->container->get('kernel')->getRootDir() . '/../' . self::RELATIVE_DIR_PATH . '/' . $package->getId();
    }

    public function getAbsoluteFilePath(DocumentPackageToGenerate $package, $filename)
    {
        return $this->container->get('kernel')->getRootDir() . '/../' . self::RELATIVE_DIR_PATH . '/' . $package->getId() . '/' . $filename;
    }

//    public function getAbsoluteFilePath($filename)
//    {
//        return $this->getAbsoluteDirPath() . '/' . $filename;
//    }

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
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->documentModel = $documentModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->invoiceBundleInvoiceModel = $invoiceBundleInvoiceModel;
    }

    public function createPackage($documents, User $createdBy, $documentEntity, $generatedDocumentEntity, $type, $params = null, $createdDate = null)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $package = new DocumentPackageToGenerate();
            $package->setStatus(DocumentPackageToGenerateModel::STATUS_WAITING_TO_PROCESS);
            $package->setAddedBy($createdBy);
            $package->setDocumentEntity($documentEntity);
            $package->setGeneratedDocumentEntity($generatedDocumentEntity);
            $package->setParams(serialize($params));
            $package->setType($type);

            if ($createdDate) {
                $package->setCreatedDate($createdDate);
            }
            $this->em->persist($package);
            $this->em->flush($package);

            foreach ($documents as $document) {
                $client = $document->getClient();

                $packageRecord = new DocumentPackageToGenerateRecord();
                $packageRecord->setDocumentId($document->getId());
                $packageRecord->setPackage($package);
                $packageRecord->setGeneratedDocumentEntity($generatedDocumentEntity);
                $packageRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_WAITING_TO_PROCESS);
                $packageRecord->setClient($client);

                $this->em->persist($packageRecord);
                $this->em->flush();
            }

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
        }
    }

    public function createPackageForCustomDocuments(
        CustomDocumentTemplate $customDocumentTemplate,
        $clients,
        User $createdBy,
        $documentEntity,
        $params = null,
        $createdDate = null
    )
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $package = new DocumentPackageToGenerate();
            $package->setCustomDocumentTemplate($customDocumentTemplate);
            $package->setStatus(DocumentPackageToGenerateModel::STATUS_WAITING_TO_PROCESS);
            $package->setAddedBy($createdBy);
            $package->setDocumentEntity($documentEntity);
            $package->setParams(serialize($params));
            $package->setType(self::TYPE_CUSTOM_DOCUMENT);

            if ($createdDate) {
                $package->setCreatedDate($createdDate);
            }
            $this->em->persist($package);
            $this->em->flush($package);

            /** @var Client $client */
            foreach ($clients as $client) {
                $packageRecord = new DocumentPackageToGenerateRecord();
                $packageRecord->setPackage($package);
                $packageRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_WAITING_TO_PROCESS);
                $packageRecord->setClient($client);

                $this->em->persist($packageRecord);
                $this->em->flush();
            }

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw new \Exception('Wystąpił błąd. Paczka nie została utworzona. Komunikat błędu: ' . $e->getMessage());
        }
    }

    public function getSingleRecordByStatus($status)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['status' => $status]);
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }

    public function getRecords($documentEntity)
    {
        return $this->em->getRepository(self::ENTITY)->findBy(['documentEntity' => $documentEntity], ['createdAt' => 'DESC']);
    }

    public function deleteRecord(DocumentPackageToGenerate $documentPackageToGenerate)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $type = $documentPackageToGenerate->getType();
            if ($type == DocumentPackageToGenerateModel::TYPE_CORRECTION) {
                $entity = $documentPackageToGenerate->getGeneratedDocumentEntity();
                $entityOption = $this->documentModel->getMappedOptionByValue($entity);
                $class = $this->easyAdminModel->getEntityClassByEntityName($entityOption);

                $records = $documentPackageToGenerate->getPackageRecords();
                /** @var DocumentPackageToGenerateRecord $record */
                foreach ($records as $record) {
                    $document = $this->em->getRepository($class)->find($record->getGeneratedDocumentId());

                    $directoryRelative = $this->easyAdminModel->getEntityDirectoryRelativeByEntityName($entityOption);

                    $this->invoiceBundleInvoiceModel->deleteFile($document, $directoryRelative);

                    $this->em->remove($document);
                }
            } elseif ($type == DocumentPackageToGenerateModel::TYPE_CUSTOM_DOCUMENT) {
                $entity = $documentPackageToGenerate->getDocumentEntity();
                $entityOption = $this->documentModel->getMappedOptionByValue($entity);
                $class = $this->easyAdminModel->getEntityClassByEntityName($entityOption);

                $records = $documentPackageToGenerate->getPackageRecords();
                /** @var DocumentPackageToGenerateRecord $record */
                foreach ($records as $record) {
                    $document = $this->em->getRepository($class)->find($record->getDocumentId());

                    $directoryRelative = $this->easyAdminModel->getEntityDirectoryRelativeByEntityName($entityOption);

                    $this->invoiceBundleInvoiceModel->deleteFile($document, $directoryRelative);

                    $this->em->remove($document);
                }
            } else {
                throw new \RuntimeException('Invalid type');
            }

            $this->em->remove($documentPackageToGenerate);
            $this->em->flush();

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
        }
    }


}