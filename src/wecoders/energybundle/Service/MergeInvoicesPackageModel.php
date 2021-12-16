<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\AccountNumberIdentifierModel;
use GCRM\CRMBundle\Service\AccountNumberMaker;
use GCRM\CRMBundle\Service\AccountNumberModel;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\ICollectiveMarkable;
use Wecoders\EnergyBundle\Entity\InvoiceCollective;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Entity\MergeInvoicesPackage;
use Wecoders\EnergyBundle\Entity\MergeInvoicesPackageRecord;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\InvoiceBundle\Service\InvoiceData;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;
use Wecoders\InvoiceBundle\Service\NumberModel;

class MergeInvoicesPackageModel
{
    const INVOICE_CLASS_NAME = 'InvoiceCollective';
    const ENTITY = 'WecodersEnergyBundle:MergeInvoicesPackage';

    private $em;
    private $container;
    private $numberModel;
    private $invoiceTemplateModel;
    private $easyAdminModel;
    private $documentModel;
    private $invoiceData;
    private $invoiceModel;
    private $invoiceModelWecoders;
    private $accountNumberMaker;

    public function __construct(
        EntityManagerInterface $em,
        ContainerInterface $container,
        NumberModel $numberModel,
        InvoiceTemplateModel $invoiceTemplateModel,
        EasyAdminModel $easyAdminModel,
        DocumentModel $documentModel,
        InvoiceData $invoiceData,
        ClientModel $clientModel,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceModel,
        InvoiceModel $invoiceModelWecoders,
        AccountNumberMaker $accountNumberMaker
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->numberModel = $numberModel;
        $this->invoiceTemplateModel = $invoiceTemplateModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->documentModel = $documentModel;
        $this->invoiceData = $invoiceData;
        $this->clientModel = $clientModel;
        $this->invoiceModel = $invoiceModel;
        $this->invoiceModelWecoders = $invoiceModelWecoders;
        $this->accountNumberMaker = $accountNumberMaker;
    }

    public function getSingleRecordByStatus($status)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['status' => $status]);
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }

    /**
     * Creates db invoice record and generates document
     *
     * @param MergeInvoicesPackage $package
     * @throws \Exception
     */
    public function process(MergeInvoicesPackage $package)
    {
        $entityConfig = $this->easyAdminModel->getEntityConfigByEntityName(self::INVOICE_CLASS_NAME);
        $templateCode = $entityConfig['invoiceTemplateCode'];

        /** @var InvoiceTemplate $invoiceTemplate */
        $invoiceTemplate = $this->invoiceTemplateModel->getTemplateRecordByCode($templateCode);
        if (!$invoiceTemplate) {
            throw new \Exception('Invoice template not set.');
        }

        $this->em->getConnection()->beginTransaction();
        try {
            $invoiceCollective = $this->createRecord($package, $invoiceTemplate, $entityConfig);
            $package->setInvoice($invoiceCollective);
            $this->em->persist($package);

            $packageRecords = $package->getPackageRecords();
            /** @var MergeInvoicesPackageRecord $packageRecord */
            foreach ($packageRecords as $packageRecord) {
                /** @var ICollectiveMarkable $invoice */
                $invoice = $packageRecord->getInvoice();
                $invoice->setIsInInvoiceCollective(true);
                $invoice->setInvoiceCollectiveNumber($invoiceCollective->getNumber());
                $this->em->persist($invoice);
            }

            $this->em->flush();

            $kernelRootDir = $this->container->get('kernel')->getRootDir();

            $templateAbsolutePath = $this->invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath());

            $directoryRelative = $this->easyAdminModel->getEntityDirectoryRelativeByEntityName(self::INVOICE_CLASS_NAME);
            $invoicePath = $this->invoiceModel->fullInvoicePath($kernelRootDir, $invoiceCollective, $directoryRelative);

            $generateDocumentMethod = $this->easyAdminModel->getEntityGenerateDocumentMethodByEntityName(self::INVOICE_CLASS_NAME);
            $this->invoiceModelWecoders->$generateDocumentMethod($invoiceCollective, $invoicePath, $templateAbsolutePath, 'ENERGY');

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();

            throw new \Exception($e->getMessage());
        }
    }

    private function createRecord(MergeInvoicesPackage $package, InvoiceTemplate $invoiceTemplate, $entityConfig)
    {
        $packageRecords = $package->getPackageRecords();

        /** @var InvoiceInterface $firstInvoice */
        $firstInvoice = $packageRecords[0]->getInvoice();

        /** @var Client $client */
        $client = $this->clientModel->getClientByBadgeId($firstInvoice->getBadgeId());

        // generate db record
        /** @var Client $client */
        $this->numberModel->init($this->container->get('kernel')->getRootDir(), $this->em, new \DateTime());


        $invoice = new InvoiceCollective();
        $invoice->setInvoiceTemplate($invoiceTemplate);
        $invoice->setType('ENERGY');

        $invoice->setPaidValue(0);
        $invoice->setIsElectronic(false);

        $invoice->setSellerTitle($firstInvoice->getSellerTitle());
        $invoice->setSellerRegon($firstInvoice->getSellerRegon());
        $invoice->setSellerNip($firstInvoice->getSellerNip());
        $invoice->setSellerZipCode($firstInvoice->getSellerZipCode());
        $invoice->setSellerCity($firstInvoice->getSellerCity());
        $invoice->setSellerBankName($firstInvoice->getSellerBankName());
        $invoice->setSellerAddress($firstInvoice->getSellerAddress());

        $invoice->setClientNip($client->getNip());
        $invoice->setClientPesel($client->getPesel());
        $invoice->setClientFullName($client->getFullName());
        $invoice->setClientZipCode($client->getZipCode());
        $invoice->setClientCity($client->getCity());
        $invoice->setClientStreet($client->getStreet());
        $invoice->setClientHouseNr($client->getHouseNr());
        $invoice->setClientApartmentNr($client->getApartmentNr());

        if ($client->getIsCompany()) {
            $invoice->setClientFullName($client->getCompanyName());
            $invoice->setRecipientCompanyName($client->getToRecipientCompanyName());
            $invoice->setRecipientNip($client->getToRecipientNip());
            $invoice->setRecipientZipCode($client->getToRecipientZipCode());
            $invoice->setRecipientCity($client->getToRecipientCity());
            $invoice->setRecipientStreet($client->getToRecipientStreet());
            $invoice->setRecipientHouseNr($client->getToRecipientHouseNr());
            $invoice->setRecipientApartmentNr($client->getToRecipientApartmentNr());
            $invoice->setPayerCompanyName($client->getToPayerCompanyName());
            $invoice->setPayerNip($client->getToPayerNip());
            $invoice->setPayerZipCode($client->getToPayerZipCode());
            $invoice->setPayerCity($client->getToPayerCity());
            $invoice->setPayerStreet($client->getToPayerStreet());
            $invoice->setPayerHouseNr($client->getToPayerHouseNr());
            $invoice->setPayerApartmentNr($client->getToPayerApartmentNr());
        } else {
            $invoice->setPayerZipCode($client->getToCorrespondenceZipCode());
            $invoice->setPayerCity($client->getToCorrespondenceCity());
            $invoice->setPayerStreet($client->getToCorrespondenceStreet());
            $invoice->setPayerHouseNr($client->getToCorrespondenceHouseNr());
            $invoice->setPayerApartmentNr($client->getToCorrespondenceApartmentNr());
        }

        $invoice->setCreatedIn($firstInvoice->getCreatedIn());
        $invoice->setBillingPeriodFrom($firstInvoice->getBillingPeriodFrom());
        $invoice->setBillingPeriodTo($firstInvoice->getBillingPeriodTo());
        $invoice->setDateOfPayment($firstInvoice->getDateOfPayment());
        $invoice->setCreatedDate($package->getCreatedDate());

        $tokensWithReplacement = [];





        /** @var InvoiceNumberSettings $numberStructure */
        $numberStructure = $this->numberModel->getSettings($entityConfig['numberSettingsCode']);
        if (!$numberStructure) {
            die('Opcje generowania numeru nie zostały ustawione.');
        }
        $invoice->setNumberStructure($numberStructure->getStructure());
        $invoice->setNumberLeadingZeros($numberStructure->getLeadingZeros());
        $invoice->setNumberResetAiAtNewMonth($numberStructure->getResetAiAtNewMonth());
        $invoice->setNumberExcludeAiFromLeadingZeros($numberStructure->getExcludeAiFromLeadingZeros());

        $generatedNumber = $this->numberModel->generate($tokensWithReplacement, $entityConfig['entityClassWithBundle'], 'number', $entityConfig['numberSettingsCode']);
        if (!$generatedNumber) {
            die('Nie można wygenerować numeru faktury. Sprawdź czy generowanie numeru faktury zostało prawidłowo ustawione.');
        }
        $invoice->setNumber($generatedNumber);


        // set invoices data
        $items = [];
        $index = 1;
        foreach ($packageRecords as $packageRecord) {
            /** @var InvoiceInterface $recordInvoice */
            $recordInvoice = $packageRecord->getInvoice();

            $item['id'] = $index++;
            $item['ppName'] = $recordInvoice->getPpName();
            $item['ppZipCode'] = $recordInvoice->getPpZipCode();
            $item['ppCity'] = $recordInvoice->getPpCity();
            $item['ppStreet'] = $recordInvoice->getPpStreet();
            $item['ppHouseNr'] = $recordInvoice->getPpHouseNr();
            $item['ppApartmentNr'] = $recordInvoice->getPpApartmentNr();
            $item['ppEnergy'] = $recordInvoice->getPpEnergy();
//            $item['tariff'] = $recordInvoice->getTariff();
            $item['sellerTariff'] = $recordInvoice->getSellerTariff();
            $item['distributionTariff'] = $recordInvoice->getDistributionTariff();
            $item['services'] = $recordInvoice->getData()[0]['services'];
            $item['consumptionByDevices'] = $recordInvoice->getConsumptionByDeviceData();
            $items[] = $item;
        }
        $invoice->setInvoicesData($items);

        // set summary data
        $items = [];
        $index = 1;
        /** @var MergeInvoicesPackageRecord $packageRecord */
        foreach ($packageRecords as $packageRecord) {
            $recordInvoice = $packageRecord->getInvoice();

            $invoiceCollectiveItem = new InvoiceCollectiveItem(
                $index++,
                $recordInvoice->getNumber(),
                $recordInvoice->getPpEnergy(),
                $recordInvoice->getSummaryNetValue(),
                23,
                $recordInvoice->getSummaryGrossValue() - $recordInvoice->getSummaryNetValue(),
                $recordInvoice->getSummaryGrossValue(),
                $recordInvoice->getConsumption(),
                $recordInvoice->getExciseValue()
            );
            $items[] = $invoiceCollectiveItem;
        }

        $invoice->setData($items);


        $data = $invoice->getData();
        $summary = [
            'netValue' => 0,
            'vatValue' => 0,
            'grossValue' => 0,
            'consumption' => 0,
            'exciseValue' => 0,
        ];

        if ($data) {
            foreach ($data as $item) {
                $summary['netValue'] += $item['netValue'];
                $summary['vatValue'] += $item['vatValue'];
                $summary['grossValue'] += $item['grossValue'];
                $summary['consumption'] += $item['consumption'];
                $summary['exciseValue'] += $item['exciseValue'];
            }
        }

        $invoice->setSummaryNetValue($summary['netValue']);
        $invoice->setSummaryGrossValue($summary['grossValue']);
        $invoice->setSummaryVatValue($summary['vatValue']);
        $invoice->setConsumption($summary['consumption']);
        $invoice->setExciseValue($summary['exciseValue']);


        // SAVE INVOICE DATA
        $this->accountNumberMaker->append($invoice);

        $this->em->persist($invoice);
        $this->em->flush($invoice);

        return $invoice;
    }
}