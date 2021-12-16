<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\CustomDocumentTemplateAndDocument;

class CustomDocumentTemplateModel
{
    const ENTITY = 'WecodersEnergyBundle:CustomDocumentTemplate';

    const EXTENSION_PDF = 'pdf';
    const EXTENSION_DOCX = 'docx';

    private $em;

    private $container;

    private $documentModel;

    public function __construct(EntityManager $em, ContainerInterface $container, DocumentModel $documentModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->documentModel = $documentModel;
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }

    public function getAbsoluteFilePath($filename)
    {
        return $this->container->getParameter('vich.path.absolute.custom_document_template_and_document') . '/' . $filename;
    }

    public function getExtension($filePath)
    {
        $mimeType = mime_content_type($filePath);

        if ($mimeType == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            return self::EXTENSION_DOCX;
        } elseif ($mimeType == 'application/pdf') {
            return self::EXTENSION_PDF;
        }

        throw new \RuntimeException('Nieobsługiwany format pliku.');
    }

    public function generateDocxFile(Client $client, ContractEnergyBase $contract, $filePath, $filename, $outputDirPath)
    {

        $template = new \PhpOffice\PhpWord\TemplateProcessor($filePath);

        $clientFullName = $client->getFullName();
        if ($client->getIsCompany()) {
            $clientFullName = $client->getCompanyName();

            $payerZipCode = $client->getToPayerZipCode();
            $payerCity = $client->getToPayerCity();
            $payerStreet = $client->getToPayerStreet();
            $payerHouseNr = $client->getToPayerHouseNr();
            $payerApartmentNr = $client->getToPayerApartmentNr();
        } else {
            $payerZipCode = $client->getToCorrespondenceZipCode();
            $payerCity = $client->getToCorrespondenceCity();
            $payerStreet = $client->getToCorrespondenceStreet();
            $payerHouseNr = $client->getToCorrespondenceHouseNr();
            $payerApartmentNr = $client->getToCorrespondenceApartmentNr();
        }

        $city = $payerCity;
        $street = $payerStreet;
        $houseNr = $payerHouseNr;
        $apartmentNr = $payerApartmentNr;

        $addressWithPrefix = $this->documentModel->manageAddress($city, $street, $houseNr, $apartmentNr, true);

        $template->setValue('clientFullName', $clientFullName);
        $template->setValue('contractNumber', $contract->getContractNumber());

        $template->setValue('payerZipCode', $payerZipCode);
        $template->setValue('payerCity', $payerCity);
        $template->setValue('payerAddressWithPrefix', $addressWithPrefix);

        if (!file_exists($outputDirPath)) {
            mkdir($outputDirPath, 0777, true);
        }

        $outputFilePath = $outputDirPath . '/' . $filename;
        $template->saveAs($outputFilePath);
        shell_exec('unoconv -f pdf ' . $outputFilePath);
    }

}