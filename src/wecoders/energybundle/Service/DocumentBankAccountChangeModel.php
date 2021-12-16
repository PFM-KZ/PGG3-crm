<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\Settings\System;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\DocumentBankAccountChange;

class DocumentBankAccountChangeModel extends DocumentModel
{
    const ENTITY = 'WecodersEnergyBundle:DocumentBankAccountChange';
    const DIR_RELATIVE_PATH = '/../var/data/uploads/bank-account-change';

    public function __construct(EntityManager $em, ContainerInterface $container, System $systemSettings, EasyAdminModel $easyAdminModel)
    {
        parent::__construct($container, $systemSettings, $em, $easyAdminModel);
    }

    public function getDirPath()
    {
        return $this->kernelRootDir . self::DIR_RELATIVE_PATH;
    }

    public function getGeneratedNotAssignedRecords()
    {
        $qb = $this->em->createQueryBuilder();
        return $qb->select('a')
            ->from(self::ENTITY, 'a')
            ->where('a.documentNumber IS NULL')
            ->andWhere('a.filePath IS NOT NULL')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }

    public function getRecordByBadgeId($badgeId)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['badgeId' => $badgeId]);
    }

    public function getRecordByDocumentNumber($documentNumber)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['badgeId' => $documentNumber]);
    }

    public function canBeAppliedToDocument($badgeId, $documentNumber)
    {
        $record = $this->em->getRepository(self::ENTITY)->findOneBy([
            'badgeId' => $badgeId,
            'documentNumber' => $documentNumber,
        ]);

        if (!$record) {
            return false;
        }
        return true;
    }

    public function getRecordToGenerate()
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from(self::ENTITY, 'a')
            ->where('a.filePath IS NULL')
            ->setMaxResults(1)
        ;

        $result = $q->getQuery()->getResult();
        if (is_array($result) && count($result)) {
            return $result[0];
        }
        return null;
    }

    public function generateDocument(DocumentBankAccountChange $documentBankAccountChange, Client $client, $contractNumber, $contractType, $documentPath, $templateAbsolutePath, $logoAbsolutePath)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $logoAbsolutePath);

        if ($client->getIsCompany()) {
            $city = $client->getToPayerCity();
            $street = $client->getToPayerStreet();
            $houseNr = $client->getToPayerHouseNr();
            $apartmentNr = $client->getToPayerApartmentNr();
            $zipCode = $client->getToPayerZipCode();
        } else {
            $city = $client->getToCorrespondenceCity();
            $street = $client->getToCorrespondenceStreet();
            $houseNr = $client->getToCorrespondenceHouseNr();
            $apartmentNr = $client->getToCorrespondenceApartmentNr();
            $zipCode = $client->getToCorrespondenceZipCode();
        }

        $addressWithPrefix = $this->manageAddress($city, $street, $houseNr, $apartmentNr, true);
        $template->setValue('clientFullName', $client->getFullName());
        $template->setValue('payerAddressWithPrefix', $addressWithPrefix);
        $template->setValue('payerCity', $city);
        $template->setValue('payerZipCode', $zipCode);
        $template->setValue('contractNumber', $contractNumber);
        $template->setValue('bankAccountNumber', $client->getBankAccountNumber());
        $template->setValue('energyTypeInWords', $contractType == 'ENERGY' ? 'Umowa sprzedaży energii elektrycznej' : 'Umowa kompleksowa dostarczania paliwa gazowego');

        $template->saveAs($documentPath . '.docx');

        shell_exec('unoconv -f pdf ' . $documentPath . '.docx');
    }

    public function applyLogo(TemplateProcessor $template, $logoAbsolutePath)
    {
        $template->setImageValue('logo', [
            'path' => $logoAbsolutePath,
            'width' => 250,
            'height' => 200,
        ]);
    }

    public function getTemplatePath($filename)
    {
        return $this->kernelRootDir . '/../web' . $this->container->getParameter('vich.path.relative.system_settings') . '/' . $filename;
    }
}