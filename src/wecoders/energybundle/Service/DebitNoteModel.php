<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\Settings\System;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\DebitNote;
use Wecoders\InvoiceBundle\Service\InvoiceData;

class DebitNoteModel extends DocumentModel
{
    const ENTITY = 'WecodersEnergyBundle:DebitNote';

    private $invoiceData;

    private $companyModel;

    public function __construct(
        EntityManager $em,
        ContainerInterface $container,
        InvoiceData $invoiceData,
        EasyAdminModel $easyAdminModel,
        System $systemSettings,
        CompanyModel $companyModel
    )
    {
        $this->invoiceData = $invoiceData;
        $this->companyModel = $companyModel;

        parent::__construct($container, $systemSettings, $em, $easyAdminModel);
    }

    public function getDocumentPath(DebitNote $debitNote, $absouluteDirPath, $filenamePrefix = '')
    {
        $dirPath = $this->generateDocumentDir($debitNote, $absouluteDirPath);

        return $dirPath . '/' . $filenamePrefix . $debitNote->getId();
    }

    public function generateDocumentDir(DebitNote $debitNote, $absouluteDirPath)
    {
        $createdDate = $debitNote->getCreatedDate();
        $dirPath = $absouluteDirPath . '/' . $createdDate->format('Y') . '/' . $createdDate->format('m');
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        }
        return $dirPath;
    }

    public function generateDebitNoteDocument(DebitNote $debitNote, $documentPath, $templateAbsolutePath, $logoAbsolutePath, $documentType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $logoAbsolutePath);

        $city = $debitNote->getClientCity();
        $street = $debitNote->getClientStreet();
        $houseNr = $debitNote->getClientHouseNr();
        $apartmentNr = $debitNote->getClientApartmentNr();

        $addressWithPrefix = $this->manageAddress($city, $street, $houseNr, $apartmentNr, true);
        $template->setValue('createdDate', $debitNote->getCreatedDate()->format('d-m-Y'));
        $template->setValue('contractNumber', $debitNote->getContractNumber());
        $template->setValue('summary', number_format($debitNote->getSummaryGrossValue(), 2, ',', ''));
        $template->setValue('summaryInWords', $this->invoiceData->getPriceInWords($debitNote->getSummaryGrossValue()));
        $template->setValue('clientFullName', $debitNote->getClientName() . ' ' . $debitNote->getClientSurname());
        $template->setValue('clientAddressWithPrefix', $addressWithPrefix);
        $template->setValue('clientZipCode', $debitNote->getClientZipCode());
        $template->setValue('clientCity', $debitNote->getClientCity());
        $template->setValue('clientAccountNumber', $debitNote->getClientAccountNumber());
        $template->setValue('content', str_replace("\r\n", '</w:t><w:br/><w:t>', $debitNote->getContent()));
        $template->setValue('energyTypeInWords', $documentType == 'ENERGY' ? 'prądu' : 'gazu');
        $template->setValue('monthsNumber', $debitNote->getMonthsNumber());
        $template->setValue('penaltyAmountPerMonth', $debitNote->getPenaltyAmountPerMonth());
        $template->setValue('signDate', $debitNote->getContractSignDate() ? $debitNote->getContractSignDate()->format('d-m-Y') : '');
        $template->setValue('contractFromDate', $debitNote->getContractFromDate() ? $debitNote->getContractFromDate()->format('d-m-Y') : '');
        $template->setValue('contractToDate', $debitNote->getContractToDate() ? $debitNote->getContractToDate()->format('d-m-Y') : '');

        /** @var Company $company */
        $company = $this->companyModel->getCompanyReadyForGenerateBankAccountNumbers();
        $template->setValue('bankName', $company->getBankName());

        $monthsInWords = 'miesiąc';
        $inMonths = [22, 23, 24, 32, 33, 34, 42, 43, 44, 52, 53, 54];
        if (in_array($debitNote->getMonthsNumber(), $inMonths) || ($debitNote->getMonthsNumber() >= 2 && $debitNote->getMonthsNumber() < 5)) {
            $monthsInWords = 'miesiące';
        } elseif ($debitNote->getMonthsNumber() >= 6) {
            $monthsInWords = 'miesięcy';
        }

        $template->setValue('monthsInWords', $monthsInWords);

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

    public function getRecordsByClient(Client $client)
    {
        $result = $this->em->getRepository(self::ENTITY)->findBy(['client' => $client]);

        if ($result) {
            $absoluteDirPath = $this->easyAdminModel->getEntityDirectoryByEntityName('DebitNote');

            /** @var DebitNote $debitNote */
            foreach ($result as $debitNote) {
                if (file_exists($this->getDocumentPath($debitNote, $absoluteDirPath)) . '.pdf') {
                    $debitNote->setIsGeneratedFileExist(true);
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