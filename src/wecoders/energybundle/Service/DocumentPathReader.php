<?php

namespace Wecoders\EnergyBundle\Service;

use GCRM\CRMBundle\Service\EasyAdminModel;
use Wecoders\EnergyBundle\Entity\IsPathReadableInterface;

class DocumentPathReader
{
    private $easyAdminModel;

    public function __construct(EasyAdminModel $easyAdminModel)
    {
        $this->easyAdminModel = $easyAdminModel;
    }

    public function read(IsPathReadableInterface $document, $absouluteDirPath, $extension = null)
    {
        $dirPath = $this->generateDocumentDir($document, $absouluteDirPath);

        return $dirPath . '/' . str_replace('/', '-', $document->getNumber()) . ($extension ? '.' . $extension : '');
    }

    public function readByEntityName(IsPathReadableInterface $document, $entityName, $extension = null)
    {
        $objectsOutputDir = $this->easyAdminModel->getEntityDirectoryByEntityName($entityName);
        if (!file_exists($objectsOutputDir)) {
            mkdir($objectsOutputDir, 0777, true);
        }

        return $this->read($document, $objectsOutputDir, $extension);
    }

    private function generateDocumentDir(IsPathReadableInterface $document, $absouluteDirPath)
    {
        /** @var \DateTime $createdDate */
        $createdDate = $document->getCreatedDate();
        $dirPath = $absouluteDirPath . '/' . $createdDate->format('Y') . '/' . $createdDate->format('m');
        if (file_exists(!$dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        return $dirPath;
    }
}