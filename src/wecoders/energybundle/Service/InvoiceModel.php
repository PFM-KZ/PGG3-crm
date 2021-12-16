<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractInterface;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\Settings\System;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Entity\InvoiceCollective;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Entity\Tariff;
use Wecoders\EnergyBundle\Event\BillingRecordGeneratedEvent;
use Wecoders\EnergyBundle\Service\BillingDocument\Document\InvoiceEstimatedSettlementCorrection;
use Wecoders\EnergyBundle\Service\BillingDocument\Document\InvoiceSettlementCorrection;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\InvoiceBundle\Service\InvoiceData;
use PhpOffice\PhpWord\TemplateProcessor;
use Wecoders\InvoiceBundle\Service\Helper;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;
use Wecoders\InvoiceBundle\Service\NumberModel;

class InvoiceModel extends DocumentModel
{
    const ROOT_RELATIVE_INVOICES_PROFORMA_PATH = 'var/data/uploads/invoices-proforma-energy';

    private $invoiceData;

    private $invoiceModel;

    private $documentTableModel;

    private $numberModel;

    private $companyModel;

    private $clientModel;

    private $invoiceTemplateModel;

    private $settlementModel;

    private $exciseModel;

    private $contractAccessor;

    public function __construct(
        EntityManager $em,
        InvoiceData $invoiceData,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceModel,
        ContainerInterface $container,
        DocumentTableModel $documentTableModel,
        NumberModel $numberModel,
        CompanyModel $companyModel,
        ClientModel $clientModel,
        InvoiceTemplateModel $invoiceTemplateModel,
        SettlementModel $settlementModel,
        ExciseModel $exciseModel,
        System $systemSettings,
        EasyAdminModel $easyAdminModel,
        ContractAccessor $contractAccessor
    )
    {
        $this->invoiceData = $invoiceData;
        $this->invoiceModel = $invoiceModel;
        $this->documentTableModel = $documentTableModel;
        $this->numberModel = $numberModel;
        $this->companyModel = $companyModel;
        $this->clientModel = $clientModel;
        $this->invoiceTemplateModel = $invoiceTemplateModel;
        $this->settlementModel = $settlementModel;
        $this->exciseModel = $exciseModel;
        $this->contractAccessor = $contractAccessor;

        parent::__construct($container, $systemSettings, $em, $easyAdminModel);
    }

    public function getInvoiceByEntity($entity, $number)
    {
        return $this->em->getRepository($entity)->findOneBy(['number' => $number]);
    }

    public function createCorrectionObject($entity, $id, $toZero = false, $flush = true)
    {
        $entityClass = $this->easyAdminModel->getEntityClassByEntityName($entity);
        $cloneAsEntityClass = $this->easyAdminModel->getCloneAsEntityClassByEntityName($entity);
        $cloneAsEntityName = $this->easyAdminModel->getCloneAsEntityByEntityName($entity);

        $invoice = $this->em->getRepository($entityClass)->find($id);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();

        /** @var Client $client */
        $client = $invoice->getClient();
        if (!$client) {
            die('Faktura nie ma przypisanego klienta.');
        }

        $createdDate = new \DateTime();

        $invoiceTemplate = $this->invoiceTemplateModel->getTemplateRecordByCode($this->easyAdminModel->getInvoiceTemplateCodeByEntityName($cloneAsEntityName));
        if (!$invoiceTemplate) {
            die('Brak szablonu generowanego dokumentu.');
        }

        /** @var InvoiceInterface $correction */
        $correction = $this->invoiceEnergyToCorrection($invoice, $invoiceTemplate, $createdDate, $cloneAsEntityClass, $toZero);

        // GENERATE NUMBER
        $numberModel = new NumberModel();

        $numberModel->init($kernelRootDir, $this->em, $createdDate);
        $tokensWithReplacement = [
            [
                'token' => '#id#',
                'replacement' => $invoice->getNumber(), // badge id for example
            ],
            [
                'token' => '#number#',
                'replacement' => $numberModel->getTokenValue($invoice->getNumber(), $invoice->getNumberStructure(), '#ai#'), // badge id for example
            ]
        ];
        $generatedNumber = $numberModel->generate($tokensWithReplacement, $cloneAsEntityClass, 'number', $this->easyAdminModel->getNumberSettingsCodeByEntityName($cloneAsEntityName));
        if (!$generatedNumber) {
            die('Nie można wygenerować numeru dokumentu. Sprawdź czy generowanie numerów zostało prawidłowo ustawione.');
        }
        $correction->setNumber($generatedNumber);

        /** @var InvoiceNumberSettings $numberStructureSettings */
        $numberStructureSettings = $numberModel->getSettings($this->easyAdminModel->getNumberSettingsCodeByEntityName($cloneAsEntityName));
        $correction->setNumberStructure($numberStructureSettings->getStructure());
        $correction->setNumberLeadingZeros($numberStructureSettings->getLeadingZeros());
        $correction->setNumberResetAiAtNewMonth($numberStructureSettings->getResetAiAtNewMonth());
        $correction->setNumberExcludeAiFromLeadingZeros($numberStructureSettings->getExcludeAiFromLeadingZeros());

        if ($flush) {
            $event = new BillingRecordGeneratedEvent($correction);
            $this->container->get('event_dispatcher')->dispatch('billing_record.pre_persist', $event);

            $this->em->persist($correction);
            $this->em->flush();

            $event = new BillingRecordGeneratedEvent($correction);
            $this->container->get('event_dispatcher')->dispatch('billing_record.post_persist', $event);
        }

        return $correction;
    }

    private function invoiceEnergyToCorrection(\Wecoders\EnergyBundle\Entity\InvoiceInterface $invoice, InvoiceTemplate $correctionTemplate, $createdDate, $className, $toZero = false)
    {
        /** @var PriceList $priceList */
        $priceList = $this->contractAccessor->accessContractPriceList($invoice->getContractNumber(), new \DateTime());
        if (!$priceList) {
            die('Nie znaleziono cennika na podstawie podanego numeru umowy (sprawdź czy istnieje): ' . $invoice->getContractNumber());
        }
        $dateOfPaymentDays = $priceList->getCorrectionDateOfPaymentDays();


        /** @var \Wecoders\EnergyBundle\Entity\InvoiceInterface $correction */
        $correction = new $className();
        $correction->setInvoice($invoice);
        $correction->setClient($invoice->getClient());
        $correction->setType($invoice->getType());

        $correction->setInvoiceTemplate($correctionTemplate);

        $invoiceDateOfPayment = $invoice->getDateOfPayment();
        $diff = $invoiceDateOfPayment->diff($createdDate);
        $diffDays = $diff->days;
        // if > 14 days - stays the same
        // if < 14 days - sets 14 days from now
        if ($createdDate >= $invoiceDateOfPayment) {
            $dateOfPayment = clone $createdDate;
            $dateOfPayment->modify('+' . $dateOfPaymentDays . ' days');
        } elseif ($diffDays >= 14) {
            $dateOfPayment = $invoiceDateOfPayment;
        } else {
            $dateOfPayment = clone $createdDate;
            $dateOfPayment->modify('+' . $dateOfPaymentDays . ' days');
        }

        $correction->setCreatedDate($createdDate);

        $correction->setDateOfPayment($dateOfPayment);
        $correction->setIsElectronic($invoice->getIsElectronic());

        $correction->setSellerTitle($invoice->getSellerTitle());
        $correction->setSellerAddress($invoice->getSellerAddress());
        $correction->setSellerZipCode($invoice->getSellerZipCode());
        $correction->setSellerCity($invoice->getSellerCity());
        $correction->setSellerNip($invoice->getSellerNip());
        $correction->setSellerBankName($invoice->getSellerBankName());
        $correction->setSellerBankAccount($invoice->getSellerBankAccount());
        $correction->setSellerRegon($invoice->getSellerRegon());

        $correction->setClientNip($invoice->getClientNip());
        $correction->setClientFullName($invoice->getClientFullName());
        $correction->setClientHouseNr($invoice->getClientHouseNr());
        $correction->setClientApartmentNr($invoice->getClientApartmentNr());
        $correction->setClientZipCode($invoice->getClientZipCode());
        $correction->setClientCity($invoice->getClientCity());
        $correction->setClientPesel($invoice->getClientPesel());
        $correction->setClientStreet($invoice->getClientStreet());

        $correction->setRecipientCompanyName($invoice->getRecipientCompanyName());
        $correction->setRecipientNip($invoice->getRecipientNip());
        $correction->setRecipientZipCode($invoice->getRecipientZipCode());
        $correction->setRecipientCity($invoice->getRecipientCity());
        $correction->setRecipientStreet($invoice->getRecipientStreet());
        $correction->setRecipientHouseNr($invoice->getRecipientHouseNr());
        $correction->setRecipientApartmentNr($invoice->getRecipientApartmentNr());

        $correction->setPayerCompanyName($invoice->getPayerCompanyName());
        $correction->setPayerNip($invoice->getPayerNip());
        $correction->setPayerZipCode($invoice->getPayerZipCode());
        $correction->setPayerCity($invoice->getPayerCity());
        $correction->setPayerStreet($invoice->getPayerStreet());
        $correction->setPayerHouseNr($invoice->getPayerHouseNr());
        $correction->setPayerApartmentNr($invoice->getPayerApartmentNr());

        $correction->setPpHouseNr($invoice->getPpHouseNr());
        $correction->setPpApartmentNr($invoice->getPpApartmentNr());
        $correction->setPpStreet($invoice->getPpStreet());
        $correction->setPpEnergy($invoice->getPpEnergy());
        $correction->setPpZipCode($invoice->getPpZipCode());
        $correction->setPpCity($invoice->getPpCity());

        $correction->setSellerTariff($invoice->getSellerTariff());
        $correction->setDistributionTariff($invoice->getDistributionTariff());

        if ($toZero) {
            $data = $invoice->getData();
            if ($data && count($data)) {
                foreach ($data as $keyDataItem => $dataItem) {
                    if (isset($data[$keyDataItem]['services']) && $data[$keyDataItem]['services'] && count($data[$keyDataItem]['services'])) {
                        foreach ($data[$keyDataItem]['services'] as $key => $service) {
                            $data[$keyDataItem]['services'][$key]['netValue'] = 0;
                            $data[$keyDataItem]['services'][$key]['vatPercentage'] = 0;
                            $data[$keyDataItem]['services'][$key]['priceValue'] = 0;
                            $data[$keyDataItem]['services'][$key]['quantity'] = 0;
                            $data[$keyDataItem]['services'][$key]['excise'] = 0;
                            $data[$keyDataItem]['services'][$key]['grossValue'] = 0;
                        }
                    }
                }
            }
            $correction->setData($data);
        } else {
            $correction->setData($invoice->getData());
        }

        $correction->setBillingPeriod($invoice->getBillingPeriod());
        $correction->setBillingPeriodFrom($invoice->getBillingPeriodFrom());
        $correction->setBillingPeriodTo($invoice->getBillingPeriodTo());
        $correction->setBadgeId($invoice->getBadgeId());
        $correction->setClientAccountNumber($invoice->getClientAccountNumber());
        $correction->setContractNumber($invoice->getContractNumber());

        $correction->setBalanceBeforeInvoice($invoice->getBalanceBeforeInvoice());

        if ($toZero) {
            $correction->setSummaryVatValue(0);
            $correction->setSummaryNetValue(0);
            $correction->setSummaryGrossValue(0);

            $correction->setIsPaid(true);
        } else {
            $correction->setSummaryVatValue($invoice->getSummaryVatValue());
            $correction->setSummaryNetValue($invoice->getSummaryNetValue());
            $correction->setSummaryGrossValue($invoice->getSummaryGrossValue());

            // paid value from invoice is copied to correction
            $correction->setPaidValue($invoice->getPaidValue());
            if ($correction->getSummaryGrossValue() == $correction->getPaidValue()) {
                $correction->setIsPaid(true);
            } else {
                $correction->setIsPaid(false);
            }
        }

        // manage frozen value (only for settlements)
        // frozen value is paid value from original document
        // frozen value cannot be higher than summary gross value of correction document
        if (
            $correction instanceof InvoiceSettlementCorrection ||
            $correction instanceof InvoiceEstimatedSettlementCorrection
        ) {
            $paidValue = $invoice->getPaidValue();
            if ($paidValue > 0) {
                if ($paidValue >= $correction->getSummaryGrossValue()) {
                    $correction->setFrozenValue($correction->getSummaryGrossValue());
                } else {
                    $correction->setFrozenValue($paidValue);
                }
            }
        }

        return $correction;
    }

    public function generateRecordInvoiceProforma(ClientAndContractInterface $clientAndContract, $energyPrices, $billingPeriodFrom, $billingPeriodTo, $createdDate, $dateOfPayment)
    {
        /** @var ContractEnergyBase $contract */
        $contract = $clientAndContract->getContract();
        /** @var Client $client */
        $client = $clientAndContract->getClient();

        if (!$contract->getPeriodInMonths()) {
            throw new \Exception('Umowa nie ma podanego okresu trwania (mc): ' . $contract->getContractNumber());
        }

        if (!$contract->getConsumption()) {
            throw new \Exception('Umowa nie ma podanego zużycia: ' . $contract->getContractNumber());
        }

        if ($contract->getType() == 'GAS' && !$contract->getOsd()) {
            throw new \Exception('Umowa nie ma przypisanego OSD: ' . $contract->getContractNumber());
        }

        $billingPeriod = clone $billingPeriodFrom;
        $billingPeriod = $billingPeriod->format('Ym');

        $document = new InvoiceProforma();


        $templateCode = $client->getIsCompany() ? 'invoice_proforma_for_company' : 'invoice_proforma';
        $invoiceTemplate = $this->invoiceTemplateModel->getTemplateRecordByCode($templateCode);
        if (!$invoiceTemplate) {
            throw new \Exception('Invoice template not set.');
        }
        $document->setInvoiceTemplate($invoiceTemplate);
        $document->setType($contract->getType());


        /** @var Company $company */
        $company = $this->companyModel->getCompanyReadyForGenerateBankAccountNumbers();
        $document->setCreatedIn($company->getCity());
        $document->setContractNumber($contract->getContractNumber());
        $document->setBadgeId($client->getAccountNumberIdentifier()->getNumber());
        $document->setType($contract->getType());

        $document->setClientAccountNumber($client->getBankAccountNumber());
        $document->setIsElectronic($contract->getIsRebateElectronicInvoice());

        $this->numberModel->init($this->kernelRootDir, $this->em, $createdDate);
        /** @var InvoiceNumberSettings $numberStructure */
        $numberStructure = $this->numberModel->getSettings('invoice_proforma');
        if (!$numberStructure) {
            throw new \Exception('Opcje generowania numeru nie zostały ustawione.');
        }
        $document->setNumberStructure($numberStructure->getStructure());
        $document->setNumberLeadingZeros($numberStructure->getLeadingZeros());
        $document->setNumberResetAiAtNewMonth($numberStructure->getResetAiAtNewMonth());
        $document->setNumberExcludeAiFromLeadingZeros($numberStructure->getExcludeAiFromLeadingZeros());

        $tokensWithReplacement = [];
        $generatedNumber = $this->numberModel->generate($tokensWithReplacement, 'WecodersEnergyBundle:InvoiceProforma', 'number', 'invoice_proforma');
        if (!$generatedNumber) {
            throw new \Exception('Nie można wygenerować numeru faktury. Sprawdź czy generowanie numeru faktury zostało prawidłowo ustawione.');
        }


        $document->setNumber($generatedNumber);
        $document->setBillingPeriod($billingPeriod);
        $document->setBillingPeriodFrom($billingPeriodFrom);
        $document->setBillingPeriodTo($billingPeriodTo);
        $document->setPaidValue(0);
        $document->setDateOfPayment($dateOfPayment);
        $document->setCreatedDate($createdDate);

        // client
        $document->setClient($client);
        $document->setClientPesel($client->getPesel());

        $document->setClientFullName($client->getFullName());
        $document->setClientNip($client->getNip());
        $document->setClientZipCode($client->getZipCode());
        $document->setClientCity($client->getCity());
        $document->setClientStreet($client->getStreet());
        $document->setClientHouseNr($client->getHouseNr());
        $document->setClientApartmentNr($client->getApartmentNr());

        if ($client->getIsCompany()) {
            $document->setRecipientCompanyName($client->getToRecipientCompanyName());
            $document->setRecipientNip($client->getToRecipientNip());
            $document->setRecipientZipCode($client->getToRecipientZipCode());
            $document->setRecipientCity($client->getToRecipientCity());
            $document->setRecipientStreet($client->getToRecipientStreet());
            $document->setRecipientHouseNr($client->getToRecipientHouseNr());
            $document->setRecipientApartmentNr($client->getToRecipientApartmentNr());

            $document->setPayerCompanyName($client->getToPayerCompanyName());
            $document->setPayerNip($client->getToPayerNip());
            $document->setPayerZipCode($client->getToPayerZipCode());
            $document->setPayerCity($client->getToPayerCity());
            $document->setPayerStreet($client->getToPayerStreet());
            $document->setPayerHouseNr($client->getToPayerHouseNr());
            $document->setPayerApartmentNr($client->getToPayerApartmentNr());
        } else {
            $document->setPayerZipCode($client->getToCorrespondenceZipCode());
            $document->setPayerCity($client->getToCorrespondenceCity());
            $document->setPayerStreet($client->getToCorrespondenceStreet());
            $document->setPayerHouseNr($client->getToCorrespondenceHouseNr());
            $document->setPayerApartmentNr($client->getToCorrespondenceApartmentNr());
        }


        // seller
        $document->setSeller(null);
        $document->setSellerTitle($company->getName());
        $document->setSellerAddress($company->getAddress());
        $document->setSellerZipCode($company->getZipcode());
        $document->setSellerCity($company->getCity());
        $document->setSellerNip($company->getNip());
        $document->setSellerRegon($company->getRegon());
        $document->setSellerBankName($company->getBankName());
        $document->setSellerBankAccount(null);

        // pp data
        $document->setPpName($contract->getPpName());
        $document->setPpEnergy($contract->getPpCodeByDate($billingPeriodFrom));
        $document->setPpZipCode($contract->getPpZipCode());
        $document->setPpCity($contract->getPpCity());
        $document->setPpStreet($contract->getPpStreet());
        $document->setPpHouseNr($contract->getPpHouseNr());
        $document->setPpApartmentNr($contract->getPpApartmentNr());
        $document->setSellerTariff($contract->getSellerTariffByDate($billingPeriodFrom));
        $document->setDistributionTariff($contract->getDistributionTariffByDate($billingPeriodFrom));

        // products
        $invoiceProducts = [];
        $records = [];
        $recordsGroupedByAreas = [];

        if ($contract->getType() == 'GAS') {
            $recordsGroupedByAreas[""] = [];

            $recordFrom = new EnergyData();
            $recordFrom->setTariff($contract->getDistributionTariffByDate($billingPeriodFrom));
            $recordFrom->setBillingPeriodFrom(null);
            $recordFrom->setBillingPeriodTo($billingPeriodFrom);
            $recordFrom->setConsumptionKwh(0);
            $recordFrom->setCalculatedConsumptionKwh(0);
            $recordFrom->setStateEnd(0);
            $recordFrom->setCode($contract->getOsd()->getOption());
            $recordFrom->setDeviceId($contract->getGasMeterFabricNr());
            $records[] = $recordFrom;

            $recordTo = new EnergyData();
            $recordTo->setTariff($contract->getDistributionTariffByDate($billingPeriodFrom));
            $recordTo->setBillingPeriodFrom($billingPeriodFrom);
            $recordTo->setBillingPeriodTo($billingPeriodTo);
            $recordTo->setConsumptionKwh($contract->getConsumption() / $contract->getPeriodInMonths());
            $recordTo->setCalculatedConsumptionKwh($contract->getConsumption() / $contract->getPeriodInMonths());
            $recordTo->setStateEnd($recordTo->getConsumptionKwh());
            $recordTo->setCode($contract->getOsd()->getOption());
            $recordTo->setDeviceId($contract->getGasMeterFabricNr());
            $records[] = $recordTo;

            $recordsGroupedByAreas = ["" => $records];
        } else { // ENERGY
            foreach ($energyPrices as $energyPrice) {
                $records = [];

                $divider = 1;
                if ($energyPrice['typeCode'] == TariffModel::TARIFF_ZONE_DAY || $energyPrice['typeCode'] == TariffModel::TARIFF_ZONE_PEAK) {
                    $divider = 24 / 16;
                }
                if ($energyPrice['typeCode'] == TariffModel::TARIFF_ZONE_NIGHT || $energyPrice['typeCode'] == TariffModel::TARIFF_ZONE_OFF_PEAK) {
                    $divider = 24 / 8;
                }
                $quantity = $contract->getConsumption();
                $quantity = $quantity / $contract->getPeriodInMonths() / $divider;
                $quantity = number_format($quantity, 5, '.', '');

                $recordFrom = new EnergyData();
                $recordFrom->setTariff($contract->getDistributionTariffByDate($billingPeriodFrom));
                $recordFrom->setArea($energyPrice['typeCode']);
                $recordFrom->setBillingPeriodFrom(null);
                $recordFrom->setBillingPeriodTo($billingPeriodFrom);
                $recordFrom->setConsumptionKwh(0);
                $recordFrom->setCalculatedConsumptionKwh(0);
                $recordFrom->setStateEnd(0);
                $recordFrom->setDeviceId($contract->getPpCounterNr());
                $records[] = $recordFrom;

                $recordTo = new EnergyData();
                $recordTo->setTariff($contract->getDistributionTariffByDate($billingPeriodFrom));
                $recordTo->setArea($energyPrice['typeCode']);
                $recordTo->setBillingPeriodFrom($billingPeriodFrom);
                $recordTo->setBillingPeriodTo($billingPeriodTo);
                $recordTo->setConsumptionKwh($quantity);
                $recordTo->setCalculatedConsumptionKwh($quantity);
                $recordTo->setStateEnd($recordTo->getConsumptionKwh());
                $recordTo->setDeviceId($contract->getPpCounterNr());
                $records[] = $recordTo;

                $recordsGroupedByAreas[$energyPrice['typeCode']] = $records;
            }
        }
        $billingPeriodFromTmp = clone $billingPeriodFrom;

        $data = $this->settlementModel->prepareData($records, $recordsGroupedByAreas, $records, $clientAndContract, $billingPeriodFromTmp, $billingPeriodTo, true);

        $index = 1;
        foreach ($data['summaryData'] as $item) {
            $invoiceProduct = new InvoiceProduct(new Helper());
            $invoiceProduct->setId($index);
            $invoiceProduct->setTitle($item['title']);
            $invoiceProduct->setVatPercentage($item['vatPercentage']);
            $invoiceProduct->setNetValue(number_format($item['netValue'], 2, '.', ''));
            $invoiceProduct->setPriceValue($item['priceValue']);
            $invoiceProduct->setGrossValue($invoiceProduct->getGrossValue());
            $invoiceProduct->setUnit($item['unit']);
            $invoiceProduct->setQuantity($item['consumption']);
            $invoiceProduct->setExcise(0);
            $invoiceProduct->setCustom([
                'area' => $item['area'],
                'deviceNumber' => isset($item['deviceId']) ? $item['deviceId'] : null,
            ]);
            $invoiceProducts[] = $invoiceProduct;
            $index++;
        }

        $document->setConsumptionByDeviceData($data['consumptionByDevices']);

        $invoiceProductGroup = new InvoiceProductGroup();
        $invoiceProductGroup->setId(1);
        $invoiceProductGroup->setProducts($invoiceProducts);
        $document->setData([$invoiceProductGroup]);

        $invoiceData = new InvoiceData(new Helper());
        $invoiceData->setProductGroups([$invoiceProductGroup]);

        $vatGroups = $invoiceData->getProductsGroupsSummaryGroupedByVat();
        $document->setSummaryNetValue($vatGroups['summary']['netValue']);
        $document->setSummaryVatValue($vatGroups['summary']['vatValue']);
        $document->setSummaryGrossValue($vatGroups['summary']['grossValue']);

        $document->recalculateConsumption();
        if ($document->getType() == 'ENERGY') {
            $exciseValue = $this->exciseModel->getExciseValueByDate($document->getBillingPeriodFrom());
            $document->setExcise($exciseValue);
            $document->recalculateExciseValue();
        } else {
            $document->setExcise(0);
            $document->setExciseValue(0);
        }

        return $document;
    }

    private function applyLogo(TemplateProcessor $template, InvoiceInterface $invoice)
    {
        // change this functionality
        $contractNumber = $invoice->getContractNumber();
        /** @var ContractEnergyBase $contract */
        $contract = null;
        if ($contractNumber) {
            $contract = $this->em->getRepository('GCRMCRMBundle:ContractGas')->findOneBy(['contractNumber' => $contractNumber]); // todo: change this
            if (!$contract) {
                $contract = $this->em->getRepository('GCRMCRMBundle:ContractEnergy')->findOneBy(['contractNumber' => $contractNumber]); // todo: change this
            }
        }
        if (!$contract) {
            die('Nie można pobrać umowy na podstawie faktury: ' . $invoice->getNumber() . ' z numerem umowy: ' . $invoice->getContractNumber());
        }

        $imgPath = $this->getLogoAbsolutePath($contract->getBrand());
        $template->setImageValue('logo', [
            'path' => $imgPath,
            'width' => 350,
            'height' => 300,
        ]);
    }

    public function generateInvoiceCollective(InvoiceCollective $invoice, $invoicePath, $templateAbsolutePath)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);

        $billingPeriodFrom = $invoice->getBillingPeriodFrom() ? $invoice->getBillingPeriodFrom()->format('d.m.Y') : '-';
        $billingPeriodTo = $invoice->getBillingPeriodTo() ? $invoice->getBillingPeriodTo()->format('d.m.Y') : '-';

        $template->setValue('documentType', 'faktura zbiorcza');
        $template->setValue('number', $invoice->getNumber());
        $template->setValue('billingPeriodFrom', $billingPeriodFrom);
        $template->setValue('billingPeriodTo', $billingPeriodTo);
        $template->setValue('createdDate', $invoice->getCreatedDate() ? $invoice->getCreatedDate()->format('d.m.Y') : '-');
        $template->setValue('sellerBankAccount', $invoice->getSellerBankAccount());
        $template->setValue('sellerBankName', $invoice->getSellerBankName());
        $template->setValue('dateOfPayment', $invoice->getDateOfPayment() ? $invoice->getDateOfPayment()->format('d.m.Y') : '-');


        $this->applyClientData($template, $invoice);

        $fullBarcodePath = $this->applyBarcode($template, $invoice->getBankAccountNumber());
        $this->applySummary($template, $invoice->getSummaryGrossValue(), 0);

        $index = 1;
        $tableWidth = 10150;
        $invoicesData = $invoice->getInvoicesData();
        $template->cloneBlock('cloneData', count($invoicesData), true, true);

        foreach ($invoicesData as $invoiceData) {
            $template->setValue('ppName#' . $index, $invoiceData['ppName']);
            $template->setValue('ppStreet#' . $index, $invoiceData['ppStreet']);
            $template->setValue('ppZipCode#' . $index, $invoiceData['ppZipCode']);
            $template->setValue('ppCity#' . $index, $invoiceData['ppCity']);
            $template->setValue('ppEnergy#' . $index, $invoiceData['ppEnergy']);
            $template->setValue('distributionTariff#' . $index, $invoiceData['distributionTariff']);
            $template->setValue('sellerTariff#' . $index, $invoiceData['sellerTariff']);

            $headings = $this->tableConsumptionHeadings(true);
            $rows = $this->tableConsumptionByDeviceRows($invoiceData['consumptionByDevices'], true);
            $this->createTable($template, 'tableConsumption#' . $index, $tableWidth, $headings, $rows);

            $headings = $this->tableDetailsHeadings(true);
            $preparedData = [];
            $preparedData[] = ['services' => $invoiceData['services']];
            $rows = $this->tableDetailsRows($preparedData, true);
            $this->createTable($template, 'tableDetails#' . $index, $tableWidth, $headings, $rows, 'center');

            $index++;
        }

        $headings = $this->tableCollectiveHeadings();
        $rows = $this->tableCollectiveRows($invoice);
        $this->createTable($template, 'tableSummary', $tableWidth, $headings, $rows, 'center');

        $template->setValue('energyTypeText', 'energii');
        $template->saveAs($invoicePath . '.docx');

        shell_exec('unoconv -f pdf ' . $invoicePath . '.docx');
        unlink($fullBarcodePath);
    }

    public function generateInvoice(InvoiceInterface $invoice, $invoicePath, $templateAbsolutePath, $contractType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $invoice);

        $this->applyDocumentMetaData($template, $invoice, 'faktura VAT');

        $isEnergy = $this->isEnergy($contractType);
        if ($isEnergy) {
            $template->setValue('energyTypeText', 'energii');
        } else {
            $template->setValue('energyTypeText', 'gazu');
        }

        $this->applyPpData($template, $invoice);
        $this->applyClientData($template, $invoice);
        $fullBarcodePath = $this->applyBarcode($template, $invoice->getClientAccountNumber());
        $balanceBeforeInvoice = $this->applyBalanceBeforeInvoice($template, $invoice->getBalanceBeforeInvoice());
        $this->applySummary($template, $invoice->getSummaryGrossValue(), $balanceBeforeInvoice);

        $content = $invoice->getContent();
        if ($content) {
            $content .= "\r\n";
        }
        $template->setValue('content', str_replace("\r\n", '</w:t><w:br/><w:t>', $content));

        $tableWidth = 10150;
        $headings = $this->tableDefaultDetailsHeadings();
        $rows = $this->tableDefaultDetailsRows($invoice->getData());
        $this->createTable($template, 'tableDetails', $tableWidth, $headings, $rows, 'center');

        if ($invoice->getIsPaid()) {
            $template->setValue('isPaidTitle', 'TAK');
            $template->setValue('summaryAdditionalText', '');
        } else {
            $template->setValue('isPaidTitle', 'NIE');
            $template->setValue('summaryAdditionalText', 'Prosimy o terminowe uregulowanie należności.');
        }

        $template->saveAs($invoicePath . '.docx');
        shell_exec('unoconv -f pdf ' . $invoicePath . '.docx');
        unlink($fullBarcodePath);
    }

    public function generateInvoiceCorrection(InvoiceInterface $invoice, $invoicePath, $templateAbsolutePath, $contractType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $invoice);

        /** @var InvoiceInterface $originalInvoice */
        $originalInvoice = $invoice->getInvoice();
        $template->setValue('originalNumber', $originalInvoice->getNumber());
        $template->setValue('originalDocumentType', 'faktura VAT');
        $template->setValue('originalSummaryGrossValue', number_format($originalInvoice->getSummaryGrossValue(), 2, ',', ''));

        $originalBalanceBeforeInvoice = $originalInvoice->getBalanceBeforeInvoice() ?: 0;
        $originalBalanceBeforeInvoiceLabel = 'Nadpłata / niedopłata';
        if ($originalBalanceBeforeInvoice > 0) {
            $originalBalanceBeforeInvoiceLabel = 'Niedopłata';
        } elseif ($originalBalanceBeforeInvoice < 0) {
            $originalBalanceBeforeInvoiceLabel = 'Nadpłata';
        }
        $template->setValue('originalBalanceBeforeInvoice', number_format($originalBalanceBeforeInvoice, 2, ',', ''));
        $template->setValue('originalBalanceBeforeInvoiceLabel', $originalBalanceBeforeInvoiceLabel);

        $isEnergy = $this->isEnergy($contractType);
        if ($isEnergy) {
            $template->setValue('energyTypeText', 'energii');
        } else {
            $template->setValue('energyTypeText', 'gazu');
        }

        $this->applyDocumentMetaData($template, $invoice, 'korekta');

        $this->applyPpData($template, $invoice);
        $this->applyClientData($template, $invoice);
        $fullBarcodePath = $this->applyBarcode($template, $invoice->getClientAccountNumber());
        $balanceBeforeInvoice = $this->applyBalanceBeforeInvoice($template, $invoice->getBalanceBeforeInvoice());

        $this->applySummary($template, $invoice->getSummaryGrossValue(), $balanceBeforeInvoice);

        $template->setValue('content', str_replace("\r\n", '</w:t><w:br/><w:t>', $invoice->getContent()));

        $tableWidth = 10150;

        $headings = $this->tableDefaultDetailsHeadings();
        $rows = $this->tableDefaultDetailsRows($originalInvoice->getData());
        $this->createTable($template, 'tableDetailsBeforeCorrection', $tableWidth, $headings, $rows, 'center');

        $headings = $this->tableDefaultDetailsHeadings();
        $rows = $this->tableDefaultDetailsRows($invoice->getData());
        $this->createTable($template, 'tableDetailsCorrection', $tableWidth, $headings, $rows, 'center');

        if ($invoice->getIsPaid()) {
            $template->setValue('isPaidTitle', 'TAK');
        } else {
            $template->setValue('isPaidTitle', 'NIE');
        }

        $template->saveAs($invoicePath . '.docx');
        shell_exec('unoconv -f pdf ' . $invoicePath . '.docx');
        unlink($fullBarcodePath);
    }

    public function generateInvoiceProforma(InvoiceInterface $invoice, $invoicePath, $templateAbsolutePath, $contractType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $invoice);

        $this->applyDocumentMetaData($template, $invoice, 'proforma');

        $isEnergy = $this->isEnergy($contractType);
        if ($isEnergy) {
            $template->setValue('energyTypeText', 'energii');
        } else {
            $template->setValue('energyTypeText', 'gazu');
        }

        $this->applyPpData($template, $invoice);
        $this->applyClientData($template, $invoice);
        $fullBarcodePath = $this->applyBarcode($template, $invoice->getClientAccountNumber());
        $balanceBeforeInvoice = $this->applyBalanceBeforeInvoice($template, $invoice->getBalanceBeforeInvoice());
        $this->applySummary($template, $invoice->getSummaryGrossValue(), $balanceBeforeInvoice);

        $tableWidth = 10150;
        $headings = $this->tableConsumptionHeadings($isEnergy);
        $rows = $this->tableConsumptionByDeviceRows($invoice->getConsumptionByDeviceData(), $isEnergy);
        $this->createTable($template, 'tableConsumption', $tableWidth, $headings, $rows);
        $headings = $this->tableDetailsHeadings($isEnergy);
        $rows = $this->tableDetailsRows($invoice->getData(), $isEnergy);
        $this->createTable($template, 'tableDetails', $tableWidth, $headings, $rows, 'center');

        if ($invoice->getIsPaid()) {
            $template->setValue('isPaidTitle', 'TAK');
            $template->setValue('summaryAdditionalText', '');
        } else {
            $template->setValue('isPaidTitle', 'NIE');
            $template->setValue('summaryAdditionalText', 'Prosimy o terminowe uregulowanie należności.');
        }

        $template->saveAs($invoicePath . '.docx');

        shell_exec('unoconv -f pdf ' . $invoicePath . '.docx');

        unlink($fullBarcodePath);
    }

    public function generateInvoiceProformaCorrection(InvoiceInterface $invoice, $invoicePath, $templateAbsolutePath, $contractType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $invoice);

        /** @var InvoiceInterface $originalInvoice */
        $originalInvoice = $invoice->getInvoice();
        $template->setValue('originalNumber', $originalInvoice->getNumber());
        $template->setValue('originalDocumentType', 'proforma');
        $template->setValue('originalSummaryGrossValue', number_format($originalInvoice->getSummaryGrossValue(), 2, ',', ''));

        $originalBalanceBeforeInvoice = $originalInvoice->getBalanceBeforeInvoice() ?: 0;
        $originalBalanceBeforeInvoiceLabel = 'Nadpłata / niedopłata';
        if ($originalBalanceBeforeInvoice > 0) {
            $originalBalanceBeforeInvoiceLabel = 'Niedopłata';
        } elseif ($originalBalanceBeforeInvoice < 0) {
            $originalBalanceBeforeInvoiceLabel = 'Nadpłata';
        }
        $template->setValue('originalBalanceBeforeInvoice', number_format($originalBalanceBeforeInvoice, 2, ',', ''));
        $template->setValue('originalBalanceBeforeInvoiceLabel', $originalBalanceBeforeInvoiceLabel);


        $isEnergy = $this->isEnergy($contractType);
        if ($isEnergy) {
            $template->setValue('energyTypeText', 'energii');
        } else {
            $template->setValue('energyTypeText', 'gazu');
        }

        $this->applyDocumentMetaData($template, $invoice, 'korekta');

        $this->applyPpData($template, $invoice);
        $this->applyClientData($template, $invoice);
        $fullBarcodePath = $this->applyBarcode($template, $invoice->getClientAccountNumber());
        $balanceBeforeInvoice = $this->applyBalanceBeforeInvoice($template, $invoice->getBalanceBeforeInvoice());

        $this->applySummary($template, $invoice->getSummaryGrossValue(), $balanceBeforeInvoice);

        $isEnergy = $this->isEnergy($contractType);

        $tableWidth = 10150;

        $headings = $this->tableDetailsHeadings($isEnergy);
        $rows = $this->tableDetailsRows($originalInvoice->getData(), $isEnergy);
        $this->createTable($template, 'tableDetailsBeforeCorrection', $tableWidth, $headings, $rows, 'center');

        $headings = $this->tableDetailsHeadings($isEnergy);
        $rows = $this->tableDetailsRows($invoice->getData(), $isEnergy);
        $this->createTable($template, 'tableDetailsCorrection', $tableWidth, $headings, $rows, 'center');

        if ($invoice->getIsPaid()) {
            $template->setValue('isPaidTitle', 'TAK');
        } else {
            $template->setValue('isPaidTitle', 'NIE');
        }

        if ($isEnergy) {
            $template->setValue('tableExciseTitle', 'Opłacona akcyza');
            $headings = $this->tableExciseHeadings();
            $rows = $this->tableExciseRows($invoice->getData());
            $this->createTable($template, 'tableExcise', $tableWidth, $headings, $rows, 'center');
        } else {
            $template->setValue('tableExciseTitle', '');
            $template->setValue('tableExcise', '');
        }

        $template->saveAs($invoicePath . '.docx');

        shell_exec('unoconv -f pdf ' . $invoicePath . '.docx');

        unlink($fullBarcodePath);
    }

    public function generateInvoiceSettlement(InvoiceInterface $invoice, $invoicePath, $templateAbsolutePath, $contractType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $invoice);

        $this->applyDocumentMetaData($template, $invoice, 'rozliczenie');

        $isEnergy = $this->isEnergy($contractType);

        $this->applyPpData($template, $invoice);
        $this->applyClientData($template, $invoice);
        $fullBarcodePath = $this->applyBarcode($template, $invoice->getClientAccountNumber());
        $balanceBeforeInvoice = $this->applySettlementBalanceBeforeInvoice($template, $invoice->getBalanceBeforeInvoice());
        $summary = $this->applySummary($template, $invoice->getSummaryGrossValue(), $balanceBeforeInvoice);

        $tableWidth = 10150;
        $headings = $this->tableConsumptionHeadings($isEnergy);
        $rows = $this->tableConsumptionByDeviceRows($invoice->getConsumptionByDeviceData(), $isEnergy);
        $this->createTable($template, 'tableConsumption', $tableWidth, $headings, $rows);

        $headings = $this->tableDetailsHeadings($isEnergy);
        $rows = $this->tableDetailsRows($invoice->getData(), $isEnergy);
        $this->createTable($template, 'tableDetails', $tableWidth, $headings, $rows, 'center');


        $isEnergy = $this->isEnergy($contractType);
        if ($isEnergy) {
            $template->setValue('energyTypeText', 'energii');
        } else {
            $template->setValue('energyTypeText', 'gazu');
        }

        if ($summary > 0) {
            $template->setValue('summaryAdditionalText', 'Prosimy o terminowe uregulowanie należności.');
        } else {
            $template->setValue('summaryAdditionalText', 'Kwota nadpłaty zostanie uwzględniona w Pana/Pani saldzie.');
        }

        if ($summary == 0 && $invoice->getBalanceBeforeInvoice() < 0 && abs($invoice->getBalanceBeforeInvoice()) > $invoice->getSummaryGrossValue()) {
            $value = abs($invoice->getSummaryGrossValue() + $invoice->getBalanceBeforeInvoice());
            $template->setValue('excessPaymentValue', number_format($value, 2, ',', ''));
        } else {
            $template->setValue('excessPaymentValue', '0,00');
        }

        $template->saveAs($invoicePath . '.docx');

        shell_exec('unoconv -f pdf ' . $invoicePath . '.docx');
        unlink($fullBarcodePath);
    }

    public function generateInvoiceEstimatedSettlement(InvoiceInterface $invoice, $invoicePath, $templateAbsolutePath, $contractType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $invoice);

        $this->applyDocumentMetaData($template, $invoice, 'szacunek');

        $isEnergy = $this->isEnergy($contractType);

        $this->applyPpData($template, $invoice);
        $this->applyClientData($template, $invoice);
        $fullBarcodePath = $this->applyBarcode($template, $invoice->getClientAccountNumber());
        $balanceBeforeInvoice = $this->applySettlementBalanceBeforeInvoice($template, $invoice->getBalanceBeforeInvoice());
        $summary = $this->applySummary($template, $invoice->getSummaryGrossValue(), $balanceBeforeInvoice);

        $tableWidth = 10150;
        $headings = $this->tableConsumptionHeadings($isEnergy);
        $rows = $this->tableConsumptionByDeviceRows($invoice->getConsumptionByDeviceData(), $isEnergy);
        $this->createTable($template, 'tableConsumption', $tableWidth, $headings, $rows);

        $headings = $this->tableDetailsHeadings($isEnergy);
        $rows = $this->tableDetailsRows($invoice->getData(), $isEnergy);
        $this->createTable($template, 'tableDetails', $tableWidth, $headings, $rows, 'center');


        $isEnergy = $this->isEnergy($contractType);
        if ($isEnergy) {
            $template->setValue('energyTypeText', 'energii');
        } else {
            $template->setValue('energyTypeText', 'gazu');
        }

        if ($summary > 0) {
            $template->setValue('summaryAdditionalText', 'Prosimy o terminowe uregulowanie należności.');
        } else {
            $template->setValue('summaryAdditionalText', 'Kwota nadpłaty zostanie uwzględniona w Pana/Pani saldzie.');
        }

        if ($summary == 0 && $invoice->getBalanceBeforeInvoice() < 0 && abs($invoice->getBalanceBeforeInvoice()) > $invoice->getSummaryGrossValue()) {
            $value = abs($invoice->getSummaryGrossValue() + $invoice->getBalanceBeforeInvoice());
            $template->setValue('excessPaymentValue', number_format($value, 2, ',', ''));
        } else {
            $template->setValue('excessPaymentValue', '0,00');
        }

        $template->saveAs($invoicePath . '.docx');

        shell_exec('unoconv -f pdf ' . $invoicePath . '.docx');
        unlink($fullBarcodePath);
    }

    public function generateInvoiceSettlementCorrection(InvoiceInterface $invoice, $invoicePath, $templateAbsolutePath, $contractType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $invoice);

        /** @var InvoiceInterface $originalInvoice */
        $originalInvoice = $invoice->getInvoice();
        $template->setValue('originalNumber', $originalInvoice->getNumber());
        $template->setValue('originalDocumentType', 'proforma');
        $template->setValue('originalSummaryGrossValue', number_format($originalInvoice->getSummaryGrossValue(), 2, ',', ''));

        $originalBalanceBeforeInvoice = $originalInvoice->getBalanceBeforeInvoice() ?: 0;
        $originalBalanceBeforeInvoiceLabel = 'Nadpłata / niedopłata';
        if ($originalBalanceBeforeInvoice > 0) {
            $originalBalanceBeforeInvoiceLabel = 'Niedopłata';
        } elseif ($originalBalanceBeforeInvoice < 0) {
            $originalBalanceBeforeInvoiceLabel = 'Nadpłata';
        }
        $template->setValue('originalBalanceBeforeInvoice', number_format($originalBalanceBeforeInvoice, 2, ',', ''));
        $template->setValue('originalBalanceBeforeInvoiceLabel', $originalBalanceBeforeInvoiceLabel);


        $isEnergy = $this->isEnergy($contractType);
        if ($isEnergy) {
            $template->setValue('energyTypeText', 'energii');
        } else {
            $template->setValue('energyTypeText', 'gazu');
        }

        $this->applyDocumentMetaData($template, $invoice, 'korekta');

        $this->applyPpData($template, $invoice);
        $this->applyClientData($template, $invoice);
        $fullBarcodePath = $this->applyBarcode($template, $invoice->getClientAccountNumber());
        $balanceBeforeInvoice = $this->applyBalanceBeforeInvoice($template, $invoice->getBalanceBeforeInvoice());

        $summary = $this->applySummary($template, $invoice->getSummaryGrossValue(), $balanceBeforeInvoice);

        $isEnergy = $this->isEnergy($contractType);

        $tableWidth = 10150;

        $headings = $this->tableDetailsHeadings($isEnergy);
        $rows = $this->tableDetailsRows($originalInvoice->getData(), $isEnergy);
        $this->createTable($template, 'tableDetailsBeforeCorrection', $tableWidth, $headings, $rows, 'center');

        $headings = $this->tableDetailsHeadings($isEnergy);
        $rows = $this->tableDetailsRows($invoice->getData(), $isEnergy);
        $this->createTable($template, 'tableDetailsCorrection', $tableWidth, $headings, $rows, 'center');

        if ($invoice->getIsPaid()) {
            $template->setValue('isPaidTitle', 'TAK');
        } else {
            $template->setValue('isPaidTitle', 'NIE');
        }

        if ($summary == 0 && $invoice->getBalanceBeforeInvoice() < 0 && abs($invoice->getBalanceBeforeInvoice()) > $invoice->getSummaryGrossValue()) {
            $value = abs($invoice->getSummaryGrossValue() + $invoice->getBalanceBeforeInvoice());
            $template->setValue('excessPaymentValue', number_format($value, 2, ',', ''));
        } else {
            $template->setValue('excessPaymentValue', '0,00');
        }

        $template->saveAs($invoicePath . '.docx');
        shell_exec('unoconv -f pdf ' . $invoicePath . '.docx');
        unlink($fullBarcodePath);
    }

    public function generateInvoiceEstimatedSettlementCorrection(InvoiceInterface $invoice, $invoicePath, $templateAbsolutePath, $contractType)
    {
        $template = new \PhpOffice\PhpWord\TemplateProcessor($templateAbsolutePath);
        $this->applyLogo($template, $invoice);

        /** @var InvoiceInterface $originalInvoice */
        $originalInvoice = $invoice->getInvoice();
        $template->setValue('originalNumber', $originalInvoice->getNumber());
        $template->setValue('originalDocumentType', 'proforma');
        $template->setValue('originalSummaryGrossValue', number_format($originalInvoice->getSummaryGrossValue(), 2, ',', ''));

        $originalBalanceBeforeInvoice = $originalInvoice->getBalanceBeforeInvoice() ?: 0;
        $originalBalanceBeforeInvoiceLabel = 'Nadpłata / niedopłata';
        if ($originalBalanceBeforeInvoice > 0) {
            $originalBalanceBeforeInvoiceLabel = 'Niedopłata';
        } elseif ($originalBalanceBeforeInvoice < 0) {
            $originalBalanceBeforeInvoiceLabel = 'Nadpłata';
        }
        $template->setValue('originalBalanceBeforeInvoice', number_format($originalBalanceBeforeInvoice, 2, ',', ''));
        $template->setValue('originalBalanceBeforeInvoiceLabel', $originalBalanceBeforeInvoiceLabel);


        $isEnergy = $this->isEnergy($contractType);
        if ($isEnergy) {
            $template->setValue('energyTypeText', 'energii');
        } else {
            $template->setValue('energyTypeText', 'gazu');
        }


        $this->applyDocumentMetaData($template, $invoice, 'korekta');


        $this->applyPpData($template, $invoice);
        $this->applyClientData($template, $invoice);
        $fullBarcodePath = $this->applyBarcode($template, $invoice->getClientAccountNumber());
        $balanceBeforeInvoice = $this->applyBalanceBeforeInvoice($template, $invoice->getBalanceBeforeInvoice());

        $summary = $this->applySummary($template, $invoice->getSummaryGrossValue(), $balanceBeforeInvoice);

        $isEnergy = $this->isEnergy($contractType);

        $tableWidth = 10150;

        $headings = $this->tableDetailsHeadings($isEnergy);
        $rows = $this->tableDetailsRows($originalInvoice->getData(), $isEnergy);
        $this->createTable($template, 'tableDetailsBeforeCorrection', $tableWidth, $headings, $rows, 'center');

        $headings = $this->tableDetailsHeadings($isEnergy);
        $rows = $this->tableDetailsRows($invoice->getData(), $isEnergy);
        $this->createTable($template, 'tableDetailsCorrection', $tableWidth, $headings, $rows, 'center');

        if ($invoice->getIsPaid()) {
            $template->setValue('isPaidTitle', 'TAK');
        } else {
            $template->setValue('isPaidTitle', 'NIE');
        }

        if ($summary == 0 && $invoice->getBalanceBeforeInvoice() < 0 && abs($invoice->getBalanceBeforeInvoice()) > $invoice->getSummaryGrossValue()) {
            $value = abs($invoice->getSummaryGrossValue() + $invoice->getBalanceBeforeInvoice());
            $template->setValue('excessPaymentValue', number_format($value, 2, ',', ''));
        } else {
            $template->setValue('excessPaymentValue', '0,00');
        }

        $template->saveAs($invoicePath . '.docx');
        shell_exec('unoconv -f pdf ' . $invoicePath . '.docx');
        unlink($fullBarcodePath);
    }

    private function removeIdTokenFromContractNumber($number)
    {
        $number = preg_replace('/#[0-9]+/', '', $number);
        return $number;
    }

    private function applyDocumentMetaData(TemplateProcessor $template, InvoiceInterface $invoice, $documentType)
    {
        $billingPeriodFrom = $invoice->getBillingPeriodFrom() ? $invoice->getBillingPeriodFrom()->format('d.m.Y') : '-';
        $billingPeriodTo = $invoice->getBillingPeriodTo() ? $invoice->getBillingPeriodTo()->format('d.m.Y') : '-';

        $template->setValue('documentType', $documentType);
        $template->setValue('number', $invoice->getNumber());
        $template->setValue('billingPeriodFrom', $billingPeriodFrom);
        $template->setValue('billingPeriodTo', $billingPeriodTo);
        $template->setValue('contractNumber', $this->removeIdTokenFromContractNumber($invoice->getContractNumber()));
        $template->setValue('createdDate', $invoice->getCreatedDate() ? $invoice->getCreatedDate()->format('d.m.Y') : '-');
        $template->setValue('sellerBankAccount', $invoice->getSellerBankAccount());
        $template->setValue('sellerBankName', $invoice->getSellerBankName());
        $template->setValue('dateOfPayment', $invoice->getDateOfPayment() ? $invoice->getDateOfPayment()->format('d.m.Y') : '-');

        return [
            'billingPeriodFrom' => $billingPeriodFrom,
            'billingPeriodTo' => $billingPeriodTo,
        ];
    }

    private function applyBalanceBeforeInvoice(TemplateProcessor $template, $balanceBeforeInvoice)
    {
        $balanceBeforeInvoice = $balanceBeforeInvoice ?: 0;
        $balanceBeforeInvoiceLabel = 'Nadpłata / niedopłata';
        if ($balanceBeforeInvoice > 0) {
            $balanceBeforeInvoiceLabel = 'Niedopłata';
        } elseif ($balanceBeforeInvoice < 0) {
            $balanceBeforeInvoiceLabel = 'Nadpłata';
        }
        $template->setValue('balanceBeforeInvoice', number_format($balanceBeforeInvoice, 2, ',', ''));
        $template->setValue('balanceBeforeInvoiceLabel', $balanceBeforeInvoiceLabel);

        return $balanceBeforeInvoice;
    }

    private function applySettlementBalanceBeforeInvoice(TemplateProcessor $template, $balanceBeforeInvoice)
    {
        $balanceBeforeInvoice = $balanceBeforeInvoice ?: 0;
        $balanceBeforeInvoiceLabel = 'Wpłacono';
        $template->setValue('balanceBeforeInvoice', number_format(abs($balanceBeforeInvoice), 2, ',', ''));
        $template->setValue('balanceBeforeInvoice', number_format(abs($balanceBeforeInvoice), 2, ',', ''));
        $template->setValue('BBI', number_format(abs($balanceBeforeInvoice), 2, ',', ''));
        $template->setValue('balanceBeforeInvoiceLabel', $balanceBeforeInvoiceLabel);
        $template->setValue('BBIL', $balanceBeforeInvoiceLabel);

        return $balanceBeforeInvoice;
    }

    private function applySummary(TemplateProcessor $template, $summaryGrossValue, $balanceBeforeInvoice)
    {
        $template->setValue('summaryGrossValue', number_format($summaryGrossValue, 2, ',', ''));
        $template->setValue('SGV', number_format($summaryGrossValue, 2, ',', ''));
        $summary = $summaryGrossValue + $balanceBeforeInvoice;
        $summary = $summary > 0 ? $summary : 0;
        $template->setValue('summary', number_format($summary, 2, ',', ''));
        $template->setValue('summaryInWords', $this->invoiceData->getPriceInWords($summary));

        return $summary;
    }

    private function applyClientData(TemplateProcessor $template, $invoice)
    {
        // buyer
        $city = $invoice->getClientCity();
        $street = $invoice->getClientStreet();
        $houseNr = $invoice->getClientHouseNr();
        $apartmentNr = $invoice->getClientApartmentNr();

        $address = $this->manageAddress($city, $street, $houseNr, $apartmentNr);
        $addressWithPrefix = $this->manageAddress($city, $street, $houseNr, $apartmentNr, true);

        $template->setValue('clientFullName', $invoice->getClientFullName()); // in company matches companyName
        $template->setValue('clientPesel', $invoice->getClientPesel());
        $template->setValue('clientNip', $invoice->getClientNip());
        $template->setValue('clientZipCode', $invoice->getClientZipCode());
        $template->setValue('clientCity', $invoice->getClientCity());
        $template->setValue('clientAddress', $address);
        $template->setValue('clientAddressWithPrefix', $addressWithPrefix);
        $template->setValue('clientStreet', $invoice->getClientStreet());
        $template->setValue('clientHouseNr', $invoice->getClientHouseNr());
        $template->setValue('clientApartmentNr', $invoice->getClientApartmentNr());


        // recipient
        $city = $invoice->getRecipientCity();
        $street = $invoice->getRecipientStreet();
        $houseNr = $invoice->getRecipientHouseNr();
        $apartmentNr = $invoice->getRecipientApartmentNr();

        $address = $this->manageAddress($city, $street, $houseNr, $apartmentNr);
        $addressWithPrefix = $this->manageAddress($city, $street, $houseNr, $apartmentNr, true);

        $template->setValue('recipientCompanyName', $invoice->getRecipientCompanyName());
        $template->setValue('recipientNip', $invoice->getRecipientNip());
        $template->setValue('recipientZipCode', $invoice->getRecipientZipCode());
        $template->setValue('recipientCity', $invoice->getRecipientCity());
        $template->setValue('recipientAddress', $address);
        $template->setValue('recipientAddressWithPrefix', $addressWithPrefix);
        $template->setValue('recipientStreet', $invoice->getRecipientStreet());
        $template->setValue('recipientHouseNr', $invoice->getRecipientHouseNr());
        $template->setValue('recipientApartmentNr', $invoice->getRecipientApartmentNr());


        // payer
        $city = $invoice->getPayerCity();
        $street = $invoice->getPayerStreet();
        $houseNr = $invoice->getPayerHouseNr();
        $apartmentNr = $invoice->getPayerApartmentNr();

        $address = $this->manageAddress($city, $street, $houseNr, $apartmentNr);
        $addressWithPrefix = $this->manageAddress($city, $street, $houseNr, $apartmentNr, true);

        $template->setValue('payerCompanyName', $invoice->getPayerCompanyName());
        $template->setValue('payerNip', $invoice->getPayerNip());
        $template->setValue('payerZipCode', $invoice->getPayerZipCode());
        $template->setValue('payerCity', $invoice->getPayerCity());
        $template->setValue('payerAddress', $address);
        $template->setValue('payerAddressWithPrefix', $addressWithPrefix);
        $template->setValue('payerStreet', $invoice->getPayerStreet());
        $template->setValue('payerHouseNr', $invoice->getPayerHouseNr());
        $template->setValue('payerApartmentNr', $invoice->getPayerApartmentNr());


        // additional
        $accountNumber = method_exists($invoice, 'getClientAccountNumber') ? $invoice->getClientAccountNumber() : $invoice->getBankAccountNumber();
        $accountNumberIdentifier = method_exists($invoice, 'getBadgeId') ? $invoice->getBadgeId() : $invoice->getAccountNumberIdentifier();
        $sellerBankName = method_exists($invoice, 'getSellerBankName') ? $invoice->getSellerBankName() : null;
        $template->setValue('bankName', $sellerBankName);
        $template->setValue('clientAccountNumber', $accountNumber);
        $template->setValue('bankAccountNumber', $accountNumber);
        $template->setValue('badgeId', $accountNumberIdentifier);
        $template->setValue('accountNumberIdentifier', $accountNumberIdentifier);
    }

    private function applyPpData(TemplateProcessor $template, InvoiceInterface $invoice)
    {
//        $template->setValue('tariff', $invoice->getTariff());
        $template->setValue('distributionTariff', $invoice->getDistributionTariff());
        $template->setValue('sellerTariff', $invoice->getSellerTariff());
        $template->setValue('ppEnergy', $invoice->getPPEnergy());
        if ($invoice->getPpStreet()) {
            $template->setValue('ppStreet', $invoice->getPpStreet());
        } else {
            $template->setValue('ppStreet', $invoice->getPpCity());
        }
        $template->setValue('ppName', $invoice->getPpName());
        $template->setValue('ppHouseNr', $invoice->getPpHouseNr());
        $template->setValue('ppApartmentNr', $invoice->getPpApartmentNr());
        $template->setValue('ppZipCode', $invoice->getPpZipCode());
        $template->setValue('ppCity', $invoice->getPpCity());
    }

    private function isEnergy($contractType)
    {
        if ($contractType == 'ENERGY') {
            return true;
        }
        return false;
    }

    private function tableConsumptionByDeviceRows($data, $isEnergy)
    {
        if (!is_array($data) || !count($data)) {
            return [];
        }

        $rows = [];
        foreach ($data as $item) {
            if ($isEnergy) {
                $add = [
                    [
                        'text' => isset($item['deviceId']) && $item['deviceId'] ? $item['deviceId'] : '',
                        'fontStyle' => [
                            'append' => [
                                'bold' => true,
                            ]
                        ],
                    ],
                    [
                        'text' => isset($item['area']) && $item['area'] ? $item['area'] : '',
                    ],
                    [
                        'text' => $item['dateFrom']->format('d.m.Y') . '-' . $item['dateTo']->format('d.m.Y'),
                    ],
                    [
                        'text' => strpos($item['consumption'], '.') ? number_format($item['consumption'], 2, ',', '') : $item['consumption'],
                    ],
                ];
            } else {
                $add = [
                    [
                        'text' => isset($item['deviceId']) && $item['deviceId'] ? $item['deviceId'] : '',
                        'fontStyle' => [
                            'append' => [
                                'bold' => true,
                            ]
                        ],
                    ],
                    [
                        'text' => $item['dateFrom']->format('d.m.Y') . '-' . $item['dateTo']->format('d.m.Y'),
                    ],
                    [
                        'text' => strpos($item['consumption'], '.') ? number_format($item['consumption'], 2, ',', '') : $item['consumption'],
                    ],
                ];
            }

            $rows[] = $add;
        }

        return $rows;
    }

    private function tableDefaultDetailsHeadings()
    {
        return [
            [
                'text' => 'Lp.',
                'width' => 450,
            ],
            [
                'text' => 'Nazwa',
                'width' => 4050,
            ],
            [
                'text' => 'J.m.',
                'width' => 600,
            ],
            [
                'text' => 'Ilość'
            ],
            [
                'text' => 'Cena netto (zł)',
                'width' => 1050,
            ],
            [
                'text' => 'Wartość netto (zł)',
                'width' => 1050,
            ],
            [
                'text' => 'Podatek Vat (zł)',
                'width' => 1050,
            ],
            [
                'text' => 'Wartość brutto (zł)',
                'width' => 1050,
            ],
        ];
    }

    private function tableCollectiveHeadings()
    {
        return [
            [
                'text' => 'Lp.',
                'width' => 450,
            ],
            [
                'text' => 'Nr punktu poboru',
            ],
            [
                'text' => 'Wartość netto [zł]',
                'width' => 1250,
            ],
            [
                'text' => 'Stawka podatku VAT [%]',
                'width' => 1450,
            ],
            [
                'text' => 'Podatek VAT [zł]',
                'width' => 1250,
            ],
            [
                'text' => 'Wartość brutto [zł]',
                'width' => 1250,
            ],
            [
                'text' => 'Zużycie [kWh]',
                'width' => 1250,
            ],
        ];
    }

    private function tableDetailsHeadings($isEnergy)
    {
        if ($isEnergy) {
            return [
                [
                    'text' => 'Lp.',
                    'width' => 450,
                ],
                [
                    'text' => 'Nazwa',
                    'width' => 2050,
                ],
                [
                    'text' => 'Strefa',
                    'width' => 2000,
                ],
                [
                    'text' => 'J.m.',
                    'width' => 600,
                ],
                [
                    'text' => 'Ilość'
                ],
                [
                    'text' => 'Cena netto (zł)',
                    'width' => 1050,
                ],
                [
                    'text' => 'Wartość netto (zł)',
                    'width' => 1050,
                ],
                [
                    'text' => 'Podatek Vat (zł)',
                    'width' => 1050,
                ],
                [
                    'text' => 'Wartość brutto (zł)',
                    'width' => 1050,
                ],
            ];
        } else {
            return [
                [
                    'text' => 'Lp.',
                    'width' => 450,
                ],
                [
                    'text' => 'Nazwa',
                    'width' => 4050,
                ],
                [
                    'text' => 'J.m.',
                    'width' => 600,
                ],
                [
                    'text' => 'Ilość'
                ],
                [
                    'text' => 'Cena netto (zł)',
                    'width' => 1050,
                ],
                [
                    'text' => 'Wartość netto (zł)',
                    'width' => 1050,
                ],
                [
                    'text' => 'Podatek Vat (zł)',
                    'width' => 1050,
                ],
                [
                    'text' => 'Wartość brutto (zł)',
                    'width' => 1050,
                ],
            ];
        }
    }

    private function tableExciseHeadings()
    {
        return [
            [
                'text' => '',
                'width' => 450,
            ],
            [
                'text' => '',
                'width' => 2050,
            ],
            [
                'text' => '',
                'width' => 2000,
            ],
            [
                'text' => '',
                'width' => 600,
            ],
            [
                'text' => '',
            ],
            [
                'text' => '',
                'width' => 1050,
            ],
            [
                'text' => '',
                'width' => 1050,
            ],
            [
                'text' => '',
                'width' => 1050,
            ],
            [
                'text' => '',
                'width' => 1050,
            ],
        ];
    }

    private function tableExciseRows($data)
    {
        if (!isset($data[0]) || !isset($data[0]['services'])) {
            return [];
        }

        $products = $data[0]['services'];
        $excise = [
            'quantity' => 0,
            'netValue' => 0,
        ];
        foreach ($products as $product) {
            if ($product['unit'] != 'kWh') {
                continue;
            }

            $excise['quantity'] += $product['quantity'];
        }
        $excise['priceValue'] = 0.005;
        $excise['netValue'] = $excise['quantity'] * $excise['priceValue'];

        $rows[] = [
            [
                'text' => '',
                'fontStyle' => [
                    'append' => [
                        'bold' => true,
                    ]
                ],
            ],
            [
                'text' => 'Akcyza',
                'pStyle' => [
                    'append' => [
                        'align' => 'left',
                    ]
                ]
            ],
            [
                'text' => 'Całodobowa',
            ],
            [
                'text' => 'kWh',
            ],
            [
                'text' => strpos($excise['quantity'], '.') ? number_format($excise['quantity'], 2, ',', '') : $excise['quantity'],
            ],
            [
                'text' => number_format($excise['priceValue'], 4, ',', ''),
            ],
            [
                'text' => number_format($excise['netValue'], 2, ',', ''),
            ],
            [
                'text' => number_format(0, 2, ',', ''),
            ],
            [
                'text' => number_format($excise['netValue'], 2, ',', ''),
            ],
        ];

        return $rows;
    }

    private function tableDetailsRows($data, $isEnergy)
    {
        if (!isset($data[0]) || !isset($data[0]['services'])) {
            return [];
        }

        $products = $data[0]['services'];
        $rows = [];
        $lp = 1;
        $invoiceProducts = [];
        foreach ($products as $product) {
            $invoiceProduct = new InvoiceProduct(new Helper());
            $invoiceProduct->setTitle($product['title']);
            $invoiceProduct->setQuantity($product['quantity']);
            $invoiceProduct->setPriceValue($product['priceValue']);
            $invoiceProduct->setNetValue($product['netValue']);
            $invoiceProduct->setVatPercentage($product['vatPercentage']);
            $invoiceProducts[] = $invoiceProduct;

            $this->addTableDetailsRow($rows, $lp, $product, $isEnergy);
            $lp++;
        }

        $invoiceRabates = [];
        if (isset($data[0]['rabates']) && $data[0]['rabates']) {
            $rabates = $data[0]['rabates'];
            foreach ($rabates as $product) {
                $invoiceProduct = new InvoiceProduct(new Helper());
                $invoiceProduct->setTitle($product['title']);
                $invoiceProduct->setNetValue($product['netValue']);
                $invoiceProduct->setVatPercentage($product['vatPercentage']);
                $invoiceRabates[] = $invoiceProduct;

                $this->addTableDetailsRow($rows, $lp, $product, $isEnergy);
                $lp++;
            }
        }

        $invoiceData = new InvoiceData(new Helper());
        $productGroup = new InvoiceProductGroup();
        $productGroup->setProducts($invoiceProducts);
        $productGroup->setRabates($invoiceRabates);
        $invoiceData->setProductGroups([$productGroup]);

        if (!count($rows)) {
            return [];
        }

        $this->addTableDetailsRowSummary($rows, $invoiceData, $isEnergy);

        return $rows;
    }

    private function tableCollectiveRows($invoice)
    {
        if (!isset($invoice->getData()[0])) {
            return [];
        }

        $rows = [];
        $lp = 1;
        foreach ($invoice->getData() as $product) {
            $this->addTableCollectiveRow($rows, $lp, $product);
            $lp++;
        }

        if (!count($rows)) {
            return [];
        }

        $this->addTableCollectiveRowSummary($invoice, $rows);

        return $rows;
    }

    private function tableDefaultDetailsRows($data)
    {
        if (!isset($data[0]) || !isset($data[0]['services'])) {
            return [];
        }

        $products = $data[0]['services'];
        $rows = [];
        $lp = 1;
        $invoiceProducts = [];
        foreach ($products as $product) {
            $invoiceProduct = new InvoiceProduct(new Helper());
            $invoiceProduct->setTitle($product['title']);
            $invoiceProduct->setQuantity($product['quantity']);
            $invoiceProduct->setPriceValue($product['priceValue']);
            $invoiceProduct->setNetValue($product['netValue']);
            $invoiceProduct->setVatPercentage($product['vatPercentage']);
            $invoiceProducts[] = $invoiceProduct;

            $this->addDefaultTableDetailsRow($rows, $lp, $product);
            $lp++;
        }

        $invoiceRabates = [];
        if (isset($data[0]['rabates']) && $data[0]['rabates']) {
            $rabates = $data[0]['rabates'];
            foreach ($rabates as $product) {
                $invoiceProduct = new InvoiceProduct(new Helper());
                $invoiceProduct->setTitle($product['title']);
                $invoiceProduct->setNetValue($product['netValue']);
                $invoiceProduct->setVatPercentage($product['vatPercentage']);
                $invoiceRabates[] = $invoiceProduct;

                $this->addDefaultTableDetailsRow($rows, $lp, $product);
                $lp++;
            }
        }

        $invoiceData = new InvoiceData(new Helper());
        $productGroup = new InvoiceProductGroup();
        $productGroup->setProducts($invoiceProducts);
        $productGroup->setRabates($invoiceRabates);
        $invoiceData->setProductGroups([$productGroup]);

        if (!count($rows)) {
            return [];
        }

        $this->addDefaultTableDetailsRowSummary($rows, $invoiceData);

        return $rows;
    }

    private function addTableCollectiveRow(&$rows, $lp, $product, $isCorrection = false)
    {
        $result = [
            [
                'text' => $isCorrection ? '' : $lp,
                'fontStyle' => [
                    'append' => [
                        'bold' => true,
                    ]
                ],
            ],
            [
                'text' => $isCorrection ? 'Korekta ' . $product['pp'] : $product['pp'],
                'pStyle' => [
                    'append' => [
                        'align' => 'left',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
            ],
        ];

        $result[] = [
            'text' => number_format($product['netValue'], 2, ',', ''),
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];

        $result[] = [
            'text' => $product['vatPercentage'],
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];

        $result[] = [
            'text' => number_format($product['vatValue'], 2, ',', ''),
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];

        $result[] = [
            'text' => number_format($product['grossValue'], 2, ',', ''),
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];

        $result[] = [
            'text' => $product['consumption'],
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];

        $rows[] = $result;
    }

    private function addTableDetailsRow(&$rows, $lp, $product, $isEnergy, $isCorrection = false)
    {
        $result = [
            [
                'text' => $isCorrection ? '' : $lp,
                'fontStyle' => [
                    'append' => [
                        'bold' => true,
                    ]
                ],
            ],
            [
                'text' => $isCorrection ? 'Korekta ' . $product['title'] : $product['title'],
                'pStyle' => [
                    'append' => [
                        'align' => 'left',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
            ],
        ];

        if ($isEnergy) {
            $result[] = [
                    'text' => isset($product['zone']) && $product['zone'] ? $product['zone'] : '',
                    'fontStyle' => [
                        'append' => [
                            'bold' => $isCorrection ? true : false,
                        ]
                    ],
                ]
            ;
        }

        $result[] = [
                'text' => isset($product['unit']) && $product['unit'] ? $product['unit'] : '',
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
            ];
        $result[] = [
                'text' => isset($product['quantity']) && strpos($product['quantity'], '.') ? number_format($product['quantity'], 2, ',', '') : (isset($product['quantity']) ? $product['quantity'] : null),
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
            ];
        $result[] = [
                'text' => isset($product['unit']) && $product['unit'] == 'kWh' ? number_format($product['priceValue'], 5, ',', '') : (isset($product['priceValue']) && number_format($product['priceValue'], 2, ',', '') ? number_format($product['priceValue'], 2, ',', '') : null),
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
            ];
        $result[] = [
                'text' => number_format($product['netValue'], 2, ',', ''),
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
            ];
        $result[] = [
                'text' => isset($product['grossValue']) ? number_format(($product['grossValue'] - $product['netValue']), 2, ',', '') : 0,
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
            ];
        $result[] = [
                'text' => isset($product['grossValue']) ? number_format($product['grossValue'], 2, ',', '') : 0,
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
            ];

        $rows[] = $result;
    }

    private function addDefaultTableDetailsRow(&$rows, $lp, $product, $isCorrection = false)
    {
        $result = [
            [
                'text' => $isCorrection ? '' : $lp,
                'fontStyle' => [
                    'append' => [
                        'bold' => true,
                    ]
                ],
            ],
            [
                'text' => $isCorrection ? 'Korekta ' . $product['title'] : $product['title'],
                'pStyle' => [
                    'append' => [
                        'align' => 'left',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
            ],
        ];

        $result[] = [
            'text' => isset($product['unit']) && $product['unit'] ? $product['unit'] : '',
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];
        $result[] = [
            'text' => isset($product['quantity']) && strpos($product['quantity'], '.') ? number_format($product['quantity'], 2, ',', '') : (isset($product['quantity']) ? $product['quantity'] : null),
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];
        $result[] = [
            'text' => isset($product['unit']) && $product['unit'] == 'kWh' ? number_format($product['priceValue'], 5, ',', '') : (isset($product['priceValue']) && number_format($product['priceValue'], 2, ',', '') ? number_format($product['priceValue'], 2, ',', '') : null),
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];
        $result[] = [
            'text' => number_format($product['netValue'], 2, ',', ''),
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];
        $result[] = [
            'text' => isset($product['grossValue']) ? number_format(($product['grossValue'] - $product['netValue']), 2, ',', '') : 0,
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];
        $result[] = [
            'text' => isset($product['grossValue']) ? number_format($product['grossValue'], 2, ',', '') : 0,
            'fontStyle' => [
                'append' => [
                    'bold' => $isCorrection ? true : false,
                ]
            ],
        ];

        $rows[] = $result;
    }

    private function addTableDetailsRowSummary(&$rows, InvoiceData $invoiceData, $isEnergy, $isCorrection = false)
    {
        $vatGroups = $invoiceData->getProductsGroupsSummaryGroupedByVat();

        $column = [];

        if ($vatGroups['dataRabates']) {
            $spaceBefore = 100;
            $spaceAfter = 50;
            $result = [
                [
                    'text' => 'SUMA PRZED BONIFIKATĄ:',
                    'fontStyle' => [
                        'append' => [
                            'bold' => true,
                        ]
                    ],
                    'cellStyle' => [
                        'new' => [
                            'gridSpan' => $isEnergy ? 6 : 5,
                            'valign' => 'center',
                            'borderBottomColor' =>'black',
                        ]
                    ],
                    'pStyle' => [
                        'append' => [
                            'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                            'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                            'align' => 'right',
                        ]
                    ]
                ],
                [
                    'text' => number_format($vatGroups['summaryProducts']['netValue'], 2, ',', ''),
                    'cellStyle' => [
                        'new' => [
                            'valign' => 'center',
                            'borderBottomColor' =>'black',
                        ]
                    ],
                    'fontStyle' => [
                        'append' => [
                            'bold' => $isCorrection ? true : false,
                        ]
                    ],
                    'pStyle' => [
                        'append' => [
                            'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                            'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                        ]
                    ]
                ],
                [
                    'text' => number_format($vatGroups['summaryProducts']['vatValue'], 2, ',', ''),
                    'cellStyle' => [
                        'new' => [
                            'valign' => 'center',
                            'borderBottomColor' =>'black',
                        ]
                    ],
                    'fontStyle' => [
                        'append' => [
                            'bold' => $isCorrection ? true : false,
                        ]
                    ],
                    'pStyle' => [
                        'append' => [
                            'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                            'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                        ]
                    ]
                ],
                [
                    'text' => number_format($vatGroups['summaryProducts']['grossValue'], 2, ',', ''),
                    'cellStyle' => [
                        'new' => [
                            'valign' => 'center',
                            'borderBottomColor' =>'black',
                        ]
                    ],
                    'fontStyle' => [
                        'append' => [
                            'bold' => $isCorrection ? true : false,
                        ]
                    ],
                    'pStyle' => [
                        'append' => [
                            'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                            'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                        ]
                    ]
                ],
            ];
            $rows[] = array_merge($column, $result);
        }

        $spaceBefore = $vatGroups['dataRabates'] ? 0 : 100;
        $spaceAfter = 100;
        $result = [
            [
                'text' => $isCorrection ? 'SUMA PO KOREKCIE:' : 'SUMA:',
                'fontStyle' => [
                    'append' => [
                        'bold' => true,
                    ]
                ],
                'cellStyle' => [
                    'new' => [
                        'gridSpan' => $isEnergy ? 6 : 5,
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                        'align' => 'right',
                    ]
                ]
            ],
            [
                'text' => number_format($vatGroups['summary']['netValue'], 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
            [
                'text' => number_format($vatGroups['summary']['vatValue'], 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
            [
                'text' => number_format($vatGroups['summary']['grossValue'], 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
        ];
        $rows[] = array_merge($column, $result);
    }

    private function addTableCollectiveRowSummary($invoice, &$rows, $isCorrection = false)
    {
        $spaceBefore = 100;
        $spaceAfter = 100;
        $result = [
            [
                'text' => $isCorrection ? 'SUMA PO KOREKCIE:' : 'SUMA:',
                'fontStyle' => [
                    'append' => [
                        'bold' => true,
                    ]
                ],
                'cellStyle' => [
                    'new' => [
                        'gridSpan' => 2,
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                        'align' => 'right',
                    ]
                ]
            ],
            [
                'text' => number_format($invoice->getSummaryNetValue(), 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
            [
                'text' => 23,
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
            [
                'text' => number_format($invoice->getSummaryVatValue(), 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
            [
                'text' => number_format($invoice->getSummaryGrossValue(), 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
            [
                'text' => number_format($invoice->getConsumption(), 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
        ];
        $rows[] = $result;
    }

    private function addDefaultTableDetailsRowSummary(&$rows, InvoiceData $invoiceData, $isCorrection = false)
    {
        $vatGroups = $invoiceData->getProductsGroupsSummaryGroupedByVat();

        $column = [];

        if ($vatGroups['dataRabates']) {
            $spaceBefore = 100;
            $spaceAfter = 50;
            $result = [
                [
                    'text' => 'SUMA PRZED BONIFIKATĄ:',
                    'fontStyle' => [
                        'append' => [
                            'bold' => true,
                        ]
                    ],
                    'cellStyle' => [
                        'new' => [
                            'gridSpan' => 5,
                            'valign' => 'center',
                            'borderBottomColor' =>'black',
                        ]
                    ],
                    'pStyle' => [
                        'append' => [
                            'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                            'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                            'align' => 'right',
                        ]
                    ]
                ],
                [
                    'text' => number_format($vatGroups['summaryProducts']['netValue'], 2, ',', ''),
                    'cellStyle' => [
                        'new' => [
                            'valign' => 'center',
                            'borderBottomColor' =>'black',
                        ]
                    ],
                    'fontStyle' => [
                        'append' => [
                            'bold' => $isCorrection ? true : false,
                        ]
                    ],
                    'pStyle' => [
                        'append' => [
                            'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                            'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                        ]
                    ]
                ],
                [
                    'text' => number_format($vatGroups['summaryProducts']['vatValue'], 2, ',', ''),
                    'cellStyle' => [
                        'new' => [
                            'valign' => 'center',
                            'borderBottomColor' =>'black',
                        ]
                    ],
                    'fontStyle' => [
                        'append' => [
                            'bold' => $isCorrection ? true : false,
                        ]
                    ],
                    'pStyle' => [
                        'append' => [
                            'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                            'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                        ]
                    ]
                ],
                [
                    'text' => number_format($vatGroups['summaryProducts']['grossValue'], 2, ',', ''),
                    'cellStyle' => [
                        'new' => [
                            'valign' => 'center',
                            'borderBottomColor' =>'black',
                        ]
                    ],
                    'fontStyle' => [
                        'append' => [
                            'bold' => $isCorrection ? true : false,
                        ]
                    ],
                    'pStyle' => [
                        'append' => [
                            'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                            'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                        ]
                    ]
                ],
            ];
            $rows[] = array_merge($column, $result);
        }

        $spaceBefore = $vatGroups['dataRabates'] ? 0 : 100;
        $spaceAfter = 100;
        $result = [
            [
                'text' => $isCorrection ? 'SUMA PO KOREKCIE:' : 'SUMA:',
                'fontStyle' => [
                    'append' => [
                        'bold' => true,
                    ]
                ],
                'cellStyle' => [
                    'new' => [
                        'gridSpan' => 5,
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                        'align' => 'right',
                    ]
                ]
            ],
            [
                'text' => number_format($vatGroups['summary']['netValue'], 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
            [
                'text' => number_format($vatGroups['summary']['vatValue'], 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
            [
                'text' => number_format($vatGroups['summary']['grossValue'], 2, ',', ''),
                'cellStyle' => [
                    'new' => [
                        'valign' => 'center',
                        'borderBottomColor' =>'black',
                    ]
                ],
                'fontStyle' => [
                    'append' => [
                        'bold' => $isCorrection ? true : false,
                    ]
                ],
                'pStyle' => [
                    'append' => [
                        'spaceBefore' => $isCorrection ? 10 : $spaceBefore,
                        'spaceAfter' => $isCorrection ? 0 : $spaceAfter,
                    ]
                ]
            ],
        ];
        $rows[] = array_merge($column, $result);
    }

    private function tableConsumptionHeadings($isEnergy)
    {
        if ($isEnergy) {
            return [
                [
                    'text' => 'Numer licznika',
                    'width' => 4000,
                ],
                [
                    'text' => 'Strefa',
                    'width' => 2000,
                ],
                [
                    'text' => 'Okres zużycia',
                    'width' => 2800,
                ],
                [
                    'text' => 'Zużycie (kWh)',
                ],
            ];
        } else {
            return [
                [
                    'text' => 'Numer licznika',
                    'width' => 6000,
                ],
                [
                    'text' => 'Okres zużycia',
                    'width' => 2800,
                ],
                [
                    'text' => 'Zużycie (kWh)',
                ],
            ];
        }
    }

}