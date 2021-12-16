<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\Alert\DocumentProcessModel;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use Matrix\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\ContractEnergyInterface;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Entity\HasIncludedDocumentsInterface;
use Wecoders\EnergyBundle\Entity\InvoiceBase;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Entity\Osd;
use Wecoders\EnergyBundle\Entity\OsdAndOsdData;
use Wecoders\EnergyBundle\Entity\OsdAndOsdDataWithData;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Entity\PriceListAndServiceData;
use Wecoders\EnergyBundle\Entity\PriceListData;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariff;
use Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice;
use Wecoders\EnergyBundle\Entity\PriceListSubscription;
use Wecoders\EnergyBundle\Entity\Service;
use Wecoders\EnergyBundle\Entity\Tariff;
use Wecoders\EnergyBundle\Entity\TariffTreatLikeLastSettlement;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;
use Wecoders\EnergyBundle\Event\BillingRecordGeneratedEvent;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityPGELodzTeren;
use Wecoders\EnergyBundle\Service\ReadingsFilters\Filter;
use Wecoders\EnergyBundle\Service\ReadingsFilters\FilterTauronNotFactured;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;
use Wecoders\InvoiceBundle\Service\Helper;
use Wecoders\InvoiceBundle\Service\InvoiceData;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;
use Wecoders\InvoiceBundle\Service\NumberModel;

class SettlementModel
{
    const ENTITY = 'WecodersEnergyBundle:EnergyData';

    private $em;

    private $container;

    private $osdModel;

    private $initializer;

    private $companyModel;

    private $clientModel;

    private $invoiceTemplateModel;

    private $contractModel;

    private $client;
    private $contract;
    private $contractAccessor;
    private $exciseModel;
    private $documentProcessModel;

    public function __construct(
        EntityManager $em,
        OsdModel $osdModel,
        Initializer $initializer,
        NumberModel $numberModel,
        CompanyModel $companyModel,
        ClientModel $clientModel,
        InvoiceTemplateModel $invoiceTemplateModel,
        ContainerInterface $container,
        ContractAccessor $contractAccessor,
        \GCRM\CRMBundle\Service\ContractModel $contractModel,
        ExciseModel $exciseModel,
        DocumentProcessModel $documentProcessModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->osdModel = $osdModel;
        $this->initializer = $initializer;
        $this->numberModel = $numberModel;
        $this->companyModel = $companyModel;
        $this->clientModel = $clientModel;
        $this->invoiceTemplateModel = $invoiceTemplateModel;
        $this->contractAccessor = $contractAccessor;
        $this->contractModel = $contractModel;
        $this->exciseModel = $exciseModel;
        $this->documentProcessModel = $documentProcessModel;
    }

    /**
     * Generates settlement document record
     *
     * @param $data
     * @param $entityConfig
     * @param $createdDate
     * @param $dateOfPayment
     * @return InvoiceInterface
     * @throws \Exception
     */
    public function generateInvoiceRecordFromData($data, $entityConfig, $type, $createdDate, $dateOfPayment)
    {
        /** @var Client $client */
        $client = $data['client'];
        /** @var ContractEnergyBase $contract */
        $contract = $data['contract'];

        $this->numberModel->init($this->container->get('kernel')->getRootDir(), $this->em, new \DateTime());

        /** @var InvoiceInterface $invoice */
        $invoice = new $entityConfig['class']();

        $templateCode = $client->getIsCompany() ? $entityConfig['invoiceTemplateCodeForCompany'] : $entityConfig['invoiceTemplateCode'];
        $invoiceTemplate = $this->invoiceTemplateModel->getTemplateRecordByCode($templateCode);
        if (!$invoiceTemplate) {
            throw new \Exception('Invoice template not set.');
        }
        $invoice->setInvoiceTemplate($invoiceTemplate);
        $invoice->setType($type);

        $invoice->setPaidValue(0);
        $invoice->setIsElectronic(false);
        $invoice->setClient($client);
        $invoice->setPpEnergy($contract->getPpCodeByDate($data['billingPeriodTo']));
        $invoice->setSellerTariff($contract->getSellerTariffByDate($data['billingPeriodTo']));
        $invoice->setDistributionTariff($contract->getDistributionTariffByDate($data['billingPeriodTo']));

        $company = $this->companyModel->getCompanyReadyForGenerateBankAccountNumbers();
        $invoice->setSellerTitle($company->getName());
        $invoice->setSellerRegon($company->getRegon());
        $invoice->setSellerNip($company->getNip());
        $invoice->setSellerZipCode($company->getZipcode());
        $invoice->setSellerCity($company->getCity());
        $invoice->setSellerBankName($company->getBankName());
        $invoice->setSellerAddress($company->getAddress());

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

        $invoice->setBadgeId($client->getAccountNumberIdentifier()->getNumber());
        $invoice->setCreatedIn($company->getCity());
        $invoice->setPpName($contract->getPpName());
        $invoice->setPpApartmentNr($contract->getPpApartmentNr());
        $invoice->setPpHouseNr($contract->getPpHouseNr());
        $invoice->setPpStreet($contract->getPpStreet());
        $invoice->setPpCity($contract->getPpCity());
        $invoice->setPpZipCode($contract->getPpZipCode());
        $invoice->setContractNumber($contract->getContractNumber());
        $invoice->setClientAccountNumber($client->getBankAccountNumber());
        $invoice->setBillingPeriodFrom($data['billingPeriodFrom']);
        $invoice->setBillingPeriodTo($data['billingPeriodTo']);
        $invoice->setDateOfPayment($dateOfPayment);
        $invoice->setCreatedDate($createdDate);

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

        $invoiceProducts = [];
        $index = 1;
        foreach ($data['summaryData'] as $item) {
            $invoiceProduct = new InvoiceProduct(new Helper());
            $invoiceProduct->setId($index);
            $invoiceProduct->setTitle($item['title']);
            $invoiceProduct->setVatPercentage(23);
            $invoiceProduct->setNetValue(number_format($item['netValue'], 2, '.', ''));
            $invoiceProduct->setPriceValue($item['priceValue']);
            $invoiceProduct->setGrossValue($invoiceProduct->getGrossValue());
            $invoiceProduct->setUnit($item['unit']);
            $invoiceProduct->setQuantity($item['consumption']);
            $invoiceProduct->setExcise(0);
            $invoiceProduct->setCustom([
                'zone' => $item['area'],
                'deviceNumber' => isset($item['deviceId']) ? $item['deviceId'] : null,
            ]);
            $invoiceProducts[] = $invoiceProduct;
            $index++;
        }

        $invoice->setConsumptionByDeviceData($data['consumptionByDevices']);

        $invoiceProductGroup = new InvoiceProductGroup();
        $invoiceProductGroup->setProducts($invoiceProducts);

        $invoiceData = new InvoiceData(new Helper());
        $invoiceData->setProductGroups([$invoiceProductGroup]);
        $invoice->setData([$invoiceProductGroup]);

        $vatGroups = $invoiceData->getProductsGroupsSummaryGroupedByVat();
        $invoice->setSummaryNetValue($vatGroups['summary']['netValue']);
        $invoice->setSummaryVatValue($vatGroups['summary']['vatValue']);
        $invoice->setSummaryGrossValue($vatGroups['summary']['grossValue']);
        // adds negative sign before (this value will be taken off from value to pay, must be - before)
        $balanceBeforeInvoice = $data['additionalData']['settlementPaymentValue'] > 0 ? -$data['additionalData']['settlementPaymentValue'] : 0;
        $invoice->setBalanceBeforeInvoice($balanceBeforeInvoice);

        $balanceToFroze = $data['additionalData']['settlementPaymentValue'] > 0 ? $data['additionalData']['settlementPaymentValue'] : 0;
        $balanceToFroze = min($balanceToFroze, $invoice->getSummaryGrossValue());
        $invoice->setFrozenValue($balanceToFroze);

        $invoice->recalculateConsumption();
        if ($invoice->getType() == 'ENERGY') {
            $exciseValue = $this->exciseModel->getExciseValueByDate($invoice->getBillingPeriodFrom());
            $invoice->setExcise($exciseValue);
            $invoice->recalculateExciseValue();
        } else {
            $invoice->setExcise(0);
            $invoice->setExciseValue(0);
        }

        if ($data['additionalData']['settlementIncludedDocuments']) {
            $settlementIncludedDocuments = [];
            /** @var InvoiceInterface $document */
            foreach ($data['additionalData']['settlementIncludedDocuments'] as $document) {
                $settlementIncludedDocument = new SettlementIncludedDocument();
                $settlementIncludedDocument = $settlementIncludedDocument->create(
                    $document->getNumber(),
                    $document->getSummaryNetValue(),
                    $document->getSummaryVatValue(),
                    $document->getSummaryGrossValue(),
                    $document->getExciseValue(),
                    $document->getBillingPeriodFrom(),
                    $document->getBillingPeriodTo()
                );

                $settlementIncludedDocuments[] = $settlementIncludedDocument;
            }
            /** @var HasIncludedDocumentsInterface $invoice */
            $invoice->setIncludedDocuments($settlementIncludedDocuments);
        }

        // SAVE INVOICE DATA
        $this->em->persist($invoice);
        $this->em->flush();

        // Dispatching the event
        $billingRecordGeneratedEvent = new BillingRecordGeneratedEvent($invoice);
        $this->container->get('event_dispatcher')->dispatch('billing_record.post_persist', $billingRecordGeneratedEvent);

        return $invoice;
    }

    public function getClientWithContractByPp($pp)
    {
        $from = 'GCRMCRMBundle:ClientAndContractEnergy';
        $join = 'GCRMCRMBundle:ContractEnergy';
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from($from, 'a')
            ->leftJoin(
                $join,
                'b',
                'WITH',
                'a.contract = b.id'
            )
            ->leftJoin(
                'GCRMCRMBundle:ContractEnergyAndPpCode',
                'c',
                'WITH',
                'b.id = c.contract'
            )
            ->where('a.client IS NOT NULL')
            ->andWhere('c.ppCode = :pp')
            ->setParameters([
                'pp' => $pp
            ])
            ->getQuery()
        ;

        $result = $q->getResult();
        if ($result) {
            return $result[0];
        }

        $from = 'GCRMCRMBundle:ClientAndContractGas';
        $join = 'GCRMCRMBundle:ContractGas';
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from($from, 'a')
            ->leftJoin(
                $join,
                'b',
                'WITH',
                'a.contract = b.id'
            )
            ->leftJoin(
                'GCRMCRMBundle:ContractGasAndPpCode',
                'c',
                'WITH',
                'b.id = c.contract'
            )
            ->where('a.client IS NOT NULL')
            ->andWhere('c.ppCode = :pp')
            ->setParameters([
                'pp' => $pp
            ])
            ->getQuery()
        ;

        $result = $q->getResult();
        if ($result) {
            return $result[0];
        }
        return null;
    }

    public function getRecordsByPps(array $pps, $dateFrom = null, $dateTo = null)
    {
        if (!count($pps)) {
            return null;
        }

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('WecodersEnergyBundle:EnergyData', 'a')

            ->orderBy('a.billingPeriodTo', 'ASC')
        ;

        // when pp have length of 9, fetching method should be with LIKE not eq
        $parameters = [];
        $dqlOr = [];
        $index = 1;
        foreach ($pps as $pp) {
            if (strlen($pp) == 9) {
                $dqlOr[] = 'a.ppCode LIKE :pp' . $index;
                $parameters['pp' . $index] = '%' . $pp . '%';
            } else {
                $dqlOr[] = 'a.ppCode = :pp' . $index;
                $dqlOr[] = 'a.ppCode = :ppWithPrefixPL' . $index;
                $parameters['pp' . $index] = $pp;
                $parameters['ppWithPrefixPL' . $index] = 'PL' . $pp;
            }
            $index++;
        }

        $q->andWhere('(' . implode(' OR ', $dqlOr) . ')');

        if ($dateFrom) {
            $q->andWhere('a.billingPeriodTo >= :dateFrom');
            $parameters['dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $q->andWhere('a.billingPeriodTo <= :dateTo');
            $parameters['dateTo'] = $dateTo;
        }

        if (count($parameters)) {
            $q->setParameters($parameters);
        }

        return $q->getQuery()->getResult();
    }

    public function getRecordsByPp($pp, $dateFrom = null, $dateTo = null)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('WecodersEnergyBundle:EnergyData', 'a')

            ->orderBy('a.billingPeriodTo', 'ASC')
        ;

        // when pp have length of 9, fetching method should be with LIKE not eq
        if (strlen($pp) == 9) {
            $q->andWhere('a.ppCode LIKE :pp');
            $parameters = ['pp' => '%' . $pp . '%'];
        } else {
            $q->andWhere('a.ppCode = :pp');
            $parameters = ['pp' => $pp];
        }

        if ($dateFrom) {
            $q->andWhere('a.billingPeriodTo >= :dateFrom');
            $parameters['dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $q->andWhere('a.billingPeriodTo <= :dateTo');
            $parameters['dateTo'] = $dateTo;
        }

        if ($parameters) {
            $q->setParameters($parameters);
        }

        return $q->getQuery()->getResult();
    }

    public function validateRecords($records, $recordsAll, $isFirstSettlement, $isLastSettlement, ContractEnergyBase $contract)
    {
        if (!$records) {
            return $records;
        }

        // sometimes first initial records does not have billing period to specified, so add it as billing period from date
        /** @var EnergyData $record */
        foreach ($recordsAll as $record) {
            if (!$record->getBillingPeriodTo() && $record->getBillingPeriodFrom()) {
                $record->setBillingPeriodTo(clone $record->getBillingPeriodFrom());
                $record->setBillingPeriodFrom(null);
                $record->setStateEnd($record->getStateStart());
                $record->setStateStart(0);
                $record->setConsumptionKwh(0);
                $record->setConsumptionKwh(0);
            }
        }
        foreach ($records as $record) {
            if (!$record->getBillingPeriodTo() && $record->getBillingPeriodFrom()) {
                $record->setBillingPeriodTo(clone $record->getBillingPeriodFrom());
                $record->setBillingPeriodFrom(null);
                $record->setStateEnd($record->getStateStart());
                $record->setStateStart(0);
                $record->setConsumptionKwh(0);
                $record->setConsumptionKwh(0);
            }
        }

        $recordsAll = $this->filterRecords($recordsAll, $isFirstSettlement, $isLastSettlement);
        $records = $this->filterRecords($records, $isFirstSettlement, $isLastSettlement);
        if (!$records) {
            return $records;
        }

        /** @var EnergyData $firstChosenRecord */
        $firstChosenRecord = $records[0];
        $this->removePostfixFromTariffInRecords($records); // some osd adds postfix for ex. gdansk adds "_GD" at the end to tariff

        // sometimes reading date is lower by 1 day than contract from date - it causes error that system calculates +1 month to calculations
        // if it happens, system need to add 1 day to this record to fix this problem
        // only in first settlement
        if ($isFirstSettlement && $firstChosenRecord->getBillingPeriodFrom()) {
            $contractFromDate = $contract->getContractFromDate();

            $tmpFirstChosenRecordBillingPeriodFrom = (clone $firstChosenRecord->getBillingPeriodFrom())->setTime(0, 0);
            if ($contractFromDate > $tmpFirstChosenRecordBillingPeriodFrom) {
                $diffDays = $tmpFirstChosenRecordBillingPeriodFrom->diff($contractFromDate)->days;
                if ($diffDays == 1) {
                    $firstChosenRecord->setBillingPeriodFrom($contractFromDate);
                } else {
                    // todo: alert than reading date mismatch contract from date
                }
            }
        }

        // Lodz teren
        // creates virtual first record for osd that does not create additional first init record with current reading date
        /** @var EnergyData $firstChosenRecord */
        if (count($records) && $firstChosenRecord->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_LODZ_TEREN) {
            // if client have already settlement, omit this functionality
            // this functionality is needed only at first settlement - osd lodz teren does not create first init record, this need to be created virtualy
            if ($isFirstSettlement) {
                $recordBefore = $this->getRecordBefore($recordsAll, $firstChosenRecord->getBillingPeriodTo());
                if (!$recordBefore) {
                    /** @var EnergyData $energyData */
                    $energyData = clone $firstChosenRecord;
                    $energyData->setBillingPeriodTo(clone $energyData->getBillingPeriodFrom());
                    $energyData->setBillingPeriodFrom(null);
                    $energyData->setStateEnd($energyData->getStateStart());
                    $energyData->setStateStart(0);
                    $energyData->setConsumptionKwh(0);
                    $energyData->setConsumptionKwh(0);
                    $energyData->isVirtual = true;

                    $records = array_prepend($records, $energyData);
                }
            }
            // split records by areas
            $newRecords = [];
            /** @var EnergyData $record */
            foreach ($records as $record) {
                $newRecords = array_merge($newRecords, ElectricityPGELodzTeren::splitByAreas($record));
            }
            $records = $newRecords;
        }

        return $records;
    }

    private function filterRecords($records, $isFirstSettlement, $isLastSettlement)
    {
        if (!$records) {
            return $records;
        }

        // filter records
        $baseFilter = new Filter($records, $isFirstSettlement, $isLastSettlement);
        $filtered = new FilterTauronNotFactured(
            $baseFilter
        );
        $records = $filtered->getRecords();

        // custom filter and group by date
        $groupedByDates = [];
        $realReadingTypes = ['R'];
        /** @var EnergyData $record */
        foreach ($records as $record) {
            $date = $record->getBillingPeriodTo()->format('Y-m-d');

            // save first record for current date and device id
            if (
                !isset($groupedByDates[$date]) ||
                !isset($groupedByDates[$date][$record->getArea()])
            ) {
                $groupedByDates[$date][$record->getArea()] = $record;
                continue;
            }

            // when device id change and current record have the same date as record before
            // then current record must be ommited because to ommit errors (this record is 0 consumption record)
            if (
                isset($groupedByDates[$date][$record->getArea()]) &&
                $groupedByDates[$date][$record->getArea()]->getDeviceId() != $record->getDeviceId()
            ) {
                continue;
            }

            // if saved record for current date is real and current record isnt real-> ommit
            if (
                in_array($groupedByDates[$date][$record->getArea()]->getReadingType(), $realReadingTypes) &&
                !in_array($record->getReadingType(), $realReadingTypes)
            ) {
                continue;
            }

            $groupedByDates[$date][$record->getArea()] = $record;
        }

        // rewrite
        if (count($groupedByDates)) {
            $result = [];
            foreach ($groupedByDates as $groupedByDate) {
                $groupedByAreas = array_values($groupedByDate);
                foreach ($groupedByAreas as $record) {
                    $result[] = $record;
                }
            }

            return $result;
        }

        return null;
    }

    private function removePostfixFromTariffInRecords(&$records)
    {
        /** @var EnergyData $record */
        foreach ($records as $record) {
            $record->setTariff($this->removePostfixFromTariff($record->getTariff()));
        }
    }

    private function removePostfixFromTariff($tariff)
    {
        $postfixes = ['_WA', '_WR', '_WO', '_GD', '_ZA', '_PO', '_TA', 'STANY_', 'Stany_'];
        foreach ($postfixes as $postfix) {
            $tariff = str_replace($postfix, '', $tariff);
        }

        return $tariff;
    }

    /**
     * Date from last billingPeriodTo most active settlement document
     * if document is not set, then returns date from 2014 year (to take all records)
     *
     * @param Client $client
     * @param Initializer $initializer
     * @return \DateTime
     */
    public function getDateFromWhichToFetchRecords(Client $client)
    {
        $this->initializer->init($client);
        $this->initializer->generate();
        /** @var InvoiceInterface $mostActiveSettlementDocument */
        $mostActiveSettlementDocument = $this->initializer->getMostActiveSettlementDocument();
        if ($mostActiveSettlementDocument) {
            return $mostActiveSettlementDocument->getBillingPeriodTo();
        }
        $date = new \DateTime();
        $date->setDate(2000, 1, 1);
        $date->setTime(0, 0, 0);
        return $date;
    }

    /**
     * @param $pp
     * @param null $dateFrom - get records with this date
     * @param null $dateTo - get records with this date
     * @return array
     */
    public function manageAndPrepareData($pp, $isTestView = false, $omitCalculateDateFrom = false, $dateFrom = null, $dateTo = null)
    {
        $clientAndContract = $this->getClientWithContractByPp($pp);
        if (!$clientAndContract) {
            throw new \Exception('Nie znaleziono klienta z umową o podanym numerze PP.');
        }

        /** @var ContractEnergyBase $contract */
        $contract = $clientAndContract->getContract();
        if (!$contract->getContractFromDate()) {
            throw new \Exception('Na umowie nie ma daty obowiązywania umowy od');
        }
//        $tariff = $contract->getTariff();
//        if (!$tariff) {
//            throw new \Exception('Na umowie nie ma taryfy');
//        }
        $priceList = $contract->getPriceListByDate($dateFrom);
        if (!$priceList) {
            throw new \Exception('Na umowie nie ma cennika');
        }


        // modify date to get records from day before
        // later date must be reset
        if ($dateFrom) {
            $dateFrom->modify('-1 day');
        }
        if ($dateTo) {
            $dateTo->modify('+1 day');
        }


        // gets client and contract records (EnergyData) from date by ppe/ppg
        $contractAndPpCodes = $contract->getContractAndPpCodes()->toArray();
        $pps = [];
        if ($contractAndPpCodes && count($contractAndPpCodes)) {
            foreach ($contractAndPpCodes as $contractAndPpCode) {
                $pps[] = $contractAndPpCode->getPpCode();
            }
        }

        $recordsAll = $this->getRecordsByPps($pps);

        // GETS LAST SETTLEMENT DOCUMENT IF CLIENT ALREADY HAVE ONE OR MORE AND SETS DATE FROM AS IT IS ON DOCUMENT BILLING PERIOD TO
        if (!$omitCalculateDateFrom) {
            $mostActiveSettlementDocument = $this->initializer->init($clientAndContract->getClient())->generate()->getMostActiveSettlementDocument();
            if ($mostActiveSettlementDocument) {
                if (!$mostActiveSettlementDocument->getBillingPeriodTo()) {
                    throw new \Exception('Ostatni dokument rozliczeniowy klienta nie ma ustawionej daty okresu rozliczeniowego do');
                }

                $mostActiveSettlementDocumentBillingPeriodTo = (clone $mostActiveSettlementDocument->getBillingPeriodTo())->setTime(0, 0);
                if (!$dateFrom) {
                    $dateFrom = $mostActiveSettlementDocumentBillingPeriodTo;
                } else {
                    $tmpDateFrom = (clone $dateFrom)->setTime(0, 0);
                    $tmpDateFrom->modify('+1 day');
                    $tmpDateLast = $mostActiveSettlementDocumentBillingPeriodTo;

                    if ($tmpDateFrom != $tmpDateLast) {
                        throw new \Exception('Wybrana data od nie pokrywa się z datą okresu rozliczeniowego do na ostatnim rozliczeniu');
                    }
                }
            }
        }

        $isFirstSettlement = $this->initializer->init($clientAndContract->getClient())->generate()->getMostActiveSettlementDocument() ? false : true;
        $isLastSettlement = $this->contractModel->hasContractEndStatus($contract);

        $records = $this->getRecordsByPps($pps, $dateFrom, $dateTo);
        $this->removeAreaPrefix($recordsAll);
        $this->removeAreaPrefix($records);

        //
        $records = $this->validateRecords($records, $recordsAll, $isFirstSettlement, $isLastSettlement, $contract);

        $errors = [];

        if (!$records) {
            $msg = 'Brak odczytów';
            if ($isTestView) {
                $errors[] = $msg;
            } else {
                throw new \Exception($msg);
            }
        }
        if (count($records) == 1) {
            $msg = 'Znaleziono tyklo 1 odczyt';
            if ($isTestView) {
                $errors[] = $msg;
            } else {
                throw new \Exception($msg);
            }
        }

        // GROUP RECORDS BY AREAS
        $recordsGroupedByArea = [];
        if ($records) {
            foreach ($records as $record) {
                if (!isset($recordsGroupedByArea[$record->getArea()])) {
                    $recordsGroupedByArea[$record->getArea()] = [];
                }
                $recordsGroupedByArea[$record->getArea()][] = $record;
            }

            // VALIDATE GROPED RECORDS
            foreach ($recordsGroupedByArea as $key => $areaRecords) {
                if (count($areaRecords) == 1) {
                    $msg = 'Znaleziono tylko 1 odczyt dla strefy: ' . $key;
                    if ($isTestView) {
                        $errors[] = $msg;
                    } else {
                        throw new \Exception($msg);
                    }
                }
            }
        }

        if ($isFirstSettlement) {
            foreach ($recordsGroupedByArea as &$groupedRecords) {
                $this->createVirtualRecords($recordsAll, $groupedRecords, $records, $contract);
            }
        }

        if ($dateTo) { // back changes
            $dateTo->modify('-1 day');
        }
        if ($dateFrom) {
            $dateFrom->modify('+1 day');
        }

//        $firstChosenRecord = isset($records[0]) ? $records[0] : null;
//        $recordBefore = $this->getRecordBefore($recordsAll, $firstChosenRecord);


        $this->appendCalculatedConsumption($recordsGroupedByArea);

        try {
            $this->validatePreparedRecords($recordsGroupedByArea);
        } catch (ReadingsValidationException $e) {
            if ($isTestView) {
                $errors[] = $e->getMessage();
            } else {
                throw new ReadingsValidationException($e->getMessage());
            }
        }

        $preparedData = $this->prepareData($records, $recordsGroupedByArea, $recordsAll, $clientAndContract, $dateFrom, $dateTo);
        $preparedData['errors'] = array_merge($errors, $preparedData['errors']);

        return $preparedData;
    }

    // creates virtual first record for osd that does not create additional first init record with current reading date
    private function createVirtualRecords(&$recordsAll, &$groupedRecords, &$records, ContractEnergyBase $contractEnergyBase)
    {
        /** @var EnergyData $firstChosenRecord */
        $firstChosenRecord = $groupedRecords[0];

        if (
            count($groupedRecords) && (
                $firstChosenRecord->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_BIALYSTOK ||
                $firstChosenRecord->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_RZESZOW ||
                $firstChosenRecord->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_SKARZYSKO_KAMIENNA ||
                $firstChosenRecord->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_LUBLIN
            )
        ) {
            $recordBefore = $this->getRecordBefore($recordsAll, $firstChosenRecord->getBillingPeriodTo());
            if (!$recordBefore) {
                /** @var EnergyData $energyData */
                $energyData = clone $firstChosenRecord;
                if ($energyData->getBillingPeriodFrom()) {
                    $energyData->setBillingPeriodTo(clone $energyData->getBillingPeriodFrom());
                    $energyData->setBillingPeriodFrom(null);
                } else {
                    $energyData->setBillingPeriodTo(clone $contractEnergyBase->getContractFromDate());
                    $energyData->setBillingPeriodFrom(clone $contractEnergyBase->getContractFromDate());
                }

                $energyData->setStateEnd($energyData->getStateStart());
                $energyData->setStateStart(0);
                $energyData->setConsumptionKwh(0);
                $energyData->setConsumptionKwh(0);
                $energyData->isVirtual = true;

                $groupedRecords = array_prepend($groupedRecords, $energyData);
                // only for information
                $records = array_prepend($records, $energyData);
            }
        }
    }

    private function removeAreaPrefix(&$records)
    {
        $areas = ['G11', 'G11p', 'C11', 'C11p', 'C11o', 'C21', 'B11', 'B21', 'A21', 'G12', 'G12p', 'G12as', 'C12b', 'C12bp', 'C22b', 'B12', 'G12r', 'G12w', 'C12a', 'C12ap', 'C22a', 'C22w', 'B22', 'B23', 'A23', 'G13'];
        $append = '/';
        foreach ($records as $record) {
            foreach ($areas as $area) {
                $prefix = $area . $append;
                $record->setArea(str_replace($prefix, '', $record->getArea()));
            }
        }
    }

    private function validatePreparedRecords($recordsGroupedByArea)
    {
        $readingValidator = new ReadingsValidator();
        foreach ($recordsGroupedByArea as $groupedRecords) {
            $readingValidator->execute($groupedRecords);
        }

        $errors = $readingValidator->getErrors();
        if (count($errors)) {
            $title = 'Błąd podczas walidacji odczytów';
            $message = implode(', ', $errors);
            $this->documentProcessModel->add(DocumentProcessModel::CODE_CRITICAL, $title, $message);

            throw new ReadingsValidationException($title . ': ' . $message);
        }
    }

    /**
     * Calculates records per each day and creates a map with dates as keys with calculated data
     *
     * @param $records
     * @return array
     * @throws \Exception
     */
    private function createRecordsDateMap(&$records, $contract, $isEnergy, $osd = null)
    {
        $data = [];

        /** @var EnergyData $record */
        for ($i = 1; $i < count($records); $i++) {
            /** @var EnergyData $recordBefore */
            $recordBefore = $records[$i - 1];
            /** @var EnergyData $recordCurrent */
            $recordCurrent = $records[$i];

            // this is a date that before record was made, system starts from this date on each record and appends +1 day
            // till goes to record current date start
            // it always starts from record before, because from this date must be calculated,
            // current record billing period to date means that system must calculate it to this date from date of record before
            $dateStart = $recordBefore->getBillingPeriodTo()->setTime(0, 0);
            $dateStartClone = clone $dateStart;
            $dateStartOfCurrentRecord = (clone $recordCurrent->getBillingPeriodTo())->setTime(0, 0);

            // validation dates
            if (!$dateStart) {
                $recordBefore->error = 'Date from record before not exist.';
                continue;
            }

            if (!$dateStartOfCurrentRecord) {
                $recordCurrent->error = 'Date from current record not exist.';
                continue;
            }

            if ($dateStartClone >= $dateStartOfCurrentRecord) {
                $recordBefore->error = 'Date from record before is higher or equal than current record.';
                continue;
            }


            // manage days (outside loop, because this value wont change inside loop)
            $days = $dateStart->diff($dateStartOfCurrentRecord)->days;
            $dayIndex = 1;


            // calculations
            // starts from current day (without last)
            // this loop calculates all days between current and before record
            do {
                // calculations for current day,
                // manage everything here to get full raport of this day in details

                // manage pricelist
                $currentCheckingDate = clone ($dateStartClone);
                $priceList = $contract->getPriceListByDate($currentCheckingDate);
                if (!$priceList) {
                    throw new \Exception('Nie można pobrać cennika na podstawie daty.');
                }

                // manage tariff
                $tariff = $contract->getSellerTariffByDate($currentCheckingDate)->getTitle();
                $distributionTariff = $this->manageRecordTariff($recordCurrent, $contract->getDistributionTariffByDate($currentCheckingDate));
                if (!$distributionTariff) {
                    throw new \Exception('Rekord nie ma taryfy: #' . $recordCurrent->getId());
                }

                // manage area
                // Matches system zone type code by record tariff and area
                // needed to fetch valid price list based on it and tariff
                $matchedZoneTypeCode = $this->matchZoneTypeCodeByTariffAndArea($recordCurrent, $tariff);
                if (!$matchedZoneTypeCode) {
                    throw new \Exception('Nie można przypisać kodu strefy na podstawie rekordu: #' . $recordCurrent->getId());
                }

                $area = TariffModel::getOptionByValue($matchedZoneTypeCode);

                // It contains zone, tariffs that matches this zone, and pricings divided by years
                /** @var PriceListData $priceListData */
                $priceListData = $this->getPriceListDataByTypeCodeAndTariff($priceList, $matchedZoneTypeCode, $tariff);
                if (!$priceListData) {
                    throw new \Exception('Nie można przypisać składowych cennika do rekordu po kodzie strefy: ' . $matchedZoneTypeCode . ' i taryfie: ' . $tariff);
                }

                // manage pricing data, gets pricing data on current date (single day), so it will be only single record
                // like - on this day price is X, there is no need to split, it will be always single pricing day (because this is a single day)
                $priceListDataAndYearWithPrices = $priceListData->getPriceListDataAndYearWithPrices();
                if (!$priceListDataAndYearWithPrices) {
                    throw new \Exception('Błędna konfiguracja cennika, nie znaleziono cen.');
                }
                $pricingData = null;
                /** @var PriceListDataAndYearWithPrice $priceListDataAndYearWithPrice */
                foreach ($priceListDataAndYearWithPrices as $priceListDataAndYearWithPrice) {
                    $pricingDateFrom = (new \DateTime())->setDate($priceListDataAndYearWithPrice->getYear(), 1, 1)->setTime(0, 0);
                    $currentCheckingDateOnlyYear = (new \DateTime())->setDate($currentCheckingDate->format('Y'), 1, 1)->setTime(0, 0);
                    $pricingData = $priceListDataAndYearWithPrice;

                    if ($currentCheckingDateOnlyYear <= $pricingDateFrom) {
                        break;
                    }
                }

                // manage Osd and subscriptions
                /** @var OsdAndOsdData $osdData */
                $chosenOsd = null;
                $osdPricingData = null;
                $chosenSubscription = null;

                if (!$isEnergy) {
                    foreach ($osd->getOsdAndOsdDatas() as $key => $osdData) {
                        $osdActiveFromDate = $osdData->getActiveFrom();
                        if ($osdActiveFromDate && $dateStartClone >= $osdActiveFromDate) {
                            $chosenOsd = $osdData;
                        }
                    }
                    if (!$chosenOsd) {
                        throw new \Exception('Błędnie ustawione daty OSD - data odczytu rekordu nie pokrywa się z datami OSD');
                    }

                    $osdPricingData = $this->managePricingOsd($chosenOsd, $tariff);
                    if (!$osdPricingData) {
                        throw new \Exception('Błędnie ustawione ceny OSD dla taryfy');
                    }



                    // manage subscription
                    $subscriptions = $priceList->getPriceListSubscriptions();
                    if (!$subscriptions) {
                        throw new \Exception('Cennik nie zawiera opłat abonamentowych');
                    }

                    /** @var PriceListSubscription $subscription */
                    foreach ($subscriptions as $subscription) {
                        $subscriptionTariff = $subscription->getTariff();
                        if (!$subscriptionTariff) {
                            continue;
                        }
                        if ($tariff == $subscriptionTariff->getTitle()) {
                            $chosenSubscription = $subscription;
                            break;
                        }
                    }
                    if (!$chosenSubscription) {
                        throw new \Exception('Nie można przypisać opłaty abonamentowej. Sprawdź czy została ustawiona.');
                    }
                }


                // set data of made calculations (per day)
                $data[$dateStartClone->format('Y-m-d')] = [
                    'dayIndex' => $dayIndex,
                    'daysToSplit' => $days,
                    'date' => clone ($dateStartClone),
                    'record' => $recordCurrent,
                    'priceList' => $priceList,
                    'pricingData' => $pricingData,
                    'deviceId' => $recordCurrent->getDeviceId(),
                    'area' => $area,
                    'tariff' => $tariff,
                    'distributionTariff' => $distributionTariff,
                    'sellerTariff' => $tariff,
                    'osd' => $osd,
                    'osdData' => $chosenOsd,
                    'osdPricingData' => $osdPricingData,
                    'subscription' => $chosenSubscription,
                    'consumptionTotal' => $recordCurrent->getCalculatedConsumptionKwh(),
                    'consumptionDay' => $recordCurrent->getCalculatedConsumptionKwh() / $days,
                ];

                // at the end, increase day
                $dateStartClone->modify('+1 day');
                // reset log data
                $dayIndex++;
                if ($dateStartClone == $dateStartOfCurrentRecord) {
                    $dayIndex = 1;
                }
            } while ($dateStartClone < $dateStartOfCurrentRecord);
        }

        return $data;
    }

    // group data by devices -> tariffs -> areas -> pricingData
    // it adds single day by day to grouped structure
    private function prepareDataMapForCalculations(&$data)
    {
        $groupedData = [];
        foreach ($data as $dayData) {
            // group by devices
            if (!array_key_exists($dayData['deviceId'], $groupedData)) {
                $groupedData[$dayData['deviceId']] = [
                    'data' => [],
                    'tree' => [],
                ];
            }
            // add data's for devices
            $groupedData[$dayData['deviceId']]['data'][] = $dayData;

            // group by tariffs
            if (!array_key_exists($dayData['tariff'], $groupedData[$dayData['deviceId']]['tree'])) {
                $groupedData[$dayData['deviceId']]['tree'][$dayData['tariff']] = [
                    'data' => [],
                    'tree' => [],
                ];
            }
            // add data's for tariffs
            $groupedData[$dayData['deviceId']]['tree'][$dayData['tariff']]['data'][] = $dayData;

            // group by areas
            if (!array_key_exists($dayData['area'], $groupedData[$dayData['deviceId']]['tree'][$dayData['tariff']]['tree'])) {
                $groupedData[$dayData['deviceId']]['tree'][$dayData['tariff']]['tree'][$dayData['area']] = [
                    'data' => [],
                    'tree' => [],
                ];
            }
            // add data's for areas
            $groupedData[$dayData['deviceId']]['tree'][$dayData['tariff']]['tree'][$dayData['area']]['data'][] = $dayData;

            // group by pricingData
            if (!array_key_exists($dayData['pricingData']->getId(), $groupedData[$dayData['deviceId']]['tree'][$dayData['tariff']]['tree'][$dayData['area']]['tree'])) {
                $groupedData[$dayData['deviceId']]['tree'][$dayData['tariff']]['tree'][$dayData['area']]['tree'][$dayData['pricingData']->getId()] = [
                    'pricingData' => $dayData['pricingData'],
                    'data' => [],
                ];
            }

            // add data's for pricing data
            $groupedData[$dayData['deviceId']]['tree'][$dayData['tariff']]['tree'][$dayData['area']]['tree'][$dayData['pricingData']->getId()]['data'][] = $dayData;
        }

        return $groupedData;
    }

    // group data by osdAndOsdData -> tariffs -> pricings
    // it adds single day by day to grouped structure
    private function prepareDataMapForCalculationsOsd(&$data)
    {
        $groupedData = [];
        foreach ($data as $dayData) {
            // group by devices
            if (!array_key_exists($dayData['osdData']->getId(), $groupedData)) {
                $groupedData[$dayData['osdData']->getId()] = [
                    'title' => $dayData['osdData']->getTitle(),
                    'data' => [],
                    'tree' => [],
                ];
            }
            // group by tariffs
            if (!array_key_exists($dayData['distributionTariff'], $groupedData[$dayData['osdData']->getId()]['tree'])) {
                $groupedData[$dayData['osdData']->getId()]['tree'][$dayData['distributionTariff']] = [
                    'data' => [],
                    'pricing' => $dayData['osdPricingData'],
                ];
            }
            // add data's for tariffs
            $groupedData[$dayData['osdData']->getId()]['tree'][$dayData['distributionTariff']]['data'][] = $dayData;
        }

        return $groupedData;
    }

    private function generateOutputForConsumption($groupedData, $isEnergy)
    {
        $calculations = [];
        foreach ($groupedData as $keyDevice => $dataDeviceStructure) {
            $treeDevice = $dataDeviceStructure['tree'];
            foreach ($treeDevice as $keyTariff => $dataTariffStructure) {
                $treeTariff = $dataTariffStructure['tree'];
                foreach ($treeTariff as $keyArea => $dataAreaStructure) {
                    $treeArea = $dataAreaStructure['tree'];
                    foreach ($treeArea as $keyPricing => $dataPricingStructure) {
                        $pricingData = $dataPricingStructure['pricingData'];

                        $consumption = 0;
                        // calculate consumption for pricing
                        foreach ($dataPricingStructure['data'] as $dayData) {
                            $consumption += $dayData['consumptionDay'];
                        }

                        // round consumption
                        $consumption = round($consumption);

                        $tmpRecord = [
                            'title' => $isEnergy ? 'Zużycie energii' : 'Paliwo gazowe',
                            'tariff' => $keyTariff,
                            'area' => $keyArea,
                            'deviceId' => $keyDevice,
                            'consumption' => $consumption,
                            'priceValue' => $pricingData->getNetValue(),
                            'netValue' => number_format($consumption * $pricingData->getNetValue(), 2, '.', ''),
                            'unit' => 'kWh',
                            'vatPercentage' => 23,
                            'isConsumptionRecord' => true,
                        ];
                        $calculations[] = $tmpRecord;
                    }
                }
            }
        }

        return $calculations;
    }

    private function generateOutputForConstantFeeSubscription($contract, $groupedData, $title, $allowRebate)
    {
        $calculations = [];
        $tmpId = 1;
        foreach ($groupedData as $monthlyData) {
            foreach ($monthlyData['tree'] as $keyPriceListData => $dataPriceListStructure) {
                $treePriceListData = $dataPriceListStructure['tree'];
                foreach ($treePriceListData as $keySubscription => $dataSubscriptionStructure) {
                    /** @var PriceListSubscription $pricingData */
                    $pricingData = $dataSubscriptionStructure['pricing'];

                    $days = count($dataSubscriptionStructure['data']);

                    // apply rebates if can
                    $monthlyRebateValue = 0;
                    if ($allowRebate) {
                        $monthlyRebateValue = $this->calculateMonthlyNetRebateValue($contract, $dataPriceListStructure['priceList']);
                    }

                    // net value with applied rebate
                    $constantValue = $pricingData->getNetValue() - $monthlyRebateValue;

                    $tmpRecord = [
                        'title' => $title,
                        'tariff' => '',
                        'area' => '',
                        'deviceId' => null,
                        'consumption' => $monthlyData['calculateMonth'] ? 1 : 0, // it defines months -> canMerge option defines it it will be merged later with other
                        'priceValue' => $constantValue,
                        'netValueBeforeRebate' => $constantValue, // this is constant fee, so all records without splitted ones, are calculated by this constant value
                        'netValueProportionalBeforeRebate' => $constantValue / $monthlyData['monthDays'] * $dataPriceListStructure['daysToCalculateConstantFees'], // this value is calculated proportionally and will be used only in splitted months, do number_format after merge
                        'netValue' => $constantValue, // this is constant fee, so all records without splitted ones, are calculated by this constant value
                        'netValueProportional' => $constantValue / $monthlyData['monthDays'] * $dataPriceListStructure['daysToCalculateConstantFees'], // this value is calculated proportionally and will be used only in splitted months, do number_format after merge
                        'unit' => 'm-c',
                        'vatPercentage' => 23,
                        // additional params to delete after merge
                        'days' => $days,
                        'daysToCalculateConstantFees' => $dataPriceListStructure['daysToCalculateConstantFees'],
                        'priceListId' => $dataPriceListStructure['priceList']->getId(),
                        'subscriptionId' => $pricingData->getId(),
                        'canMerge' => count($monthlyData['tree']) == 1 ? true : false, // if true, that means month was on same parameters without changes, there was not any tariff change or smt.
                        'rebateValue' => $monthlyRebateValue,
                        'id' => $tmpId++, // helper temp id
                    ];
                    $calculations[] = $tmpRecord;
                }
            }
        }

        return $this->mergeCalculations($calculations, ['priceListId', 'subscriptionId']);
    }

    private function generateOutputForConstantFee($contract, $groupedData, $title, $getPricingMethod, $allowRebate)
    {
        $calculations = [];
        $tmpId = 1;
        foreach ($groupedData as $monthlyData) {
            foreach ($monthlyData['tree'] as $keyPriceListData => $dataPriceListStructure) {
                /** @var PriceList $pricingData */
                $pricingData = $dataPriceListStructure['priceList'];
                $constantValue = $pricingData->$getPricingMethod();
                // ommit records if there is no fee value set
                if (!$constantValue) {
                    continue;
                }

                $days = count($dataPriceListStructure['data']);

                // apply rebates if can
                $monthlyRebateValue = 0;
                if ($allowRebate) {
                    $monthlyRebateValue = $this->calculateMonthlyNetRebateValue($contract, $pricingData);
                }

                // apply rebate
                $constantValue = $pricingData->$getPricingMethod() - $monthlyRebateValue;

                $tmpRecord = [
                    'title' => $title,
                    'tariff' => '',
                    'area' => '',
                    'deviceId' => null,
                    'consumption' => $monthlyData['calculateMonth'] ? 1 : 0, // it defines months -> canMerge option defines it it will be merged later with other
                    'priceValue' => $constantValue,
                    'netValueBeforeRebate' => $constantValue, // this is constant fee, so all records without splitted ones, are calculated by this constant value
                    'netValueProportionalBeforeRebate' => $constantValue / $monthlyData['monthDays'] * $dataPriceListStructure['daysToCalculateConstantFees'], // this value is calculated proportionally and will be used only in splitted months, do number_format after merge
                    'netValue' => $constantValue, // this is constant fee, so all records without splitted ones, are calculated by this constant value
                    'netValueProportional' => $constantValue / $monthlyData['monthDays'] * $dataPriceListStructure['daysToCalculateConstantFees'], // this value is calculated proportionally and will be used only in splitted months, do number_format after merge
                    'unit' => 'm-c',
                    'vatPercentage' => 23,
                    // additional params to delete after merge
                    'days' => $days,
                    'daysToCalculateConstantFees' => $dataPriceListStructure['daysToCalculateConstantFees'],
                    'priceListId' => $dataPriceListStructure['priceList']->getId(),
                    'subscriptionId' => $pricingData->getId(),
                    'canMerge' => count($monthlyData['tree']) == 1 ? true : false, // if true, that means month was on same parameters without changes, there was not any tariff change or smt.
                    'rebateValue' => $monthlyRebateValue,
//                    'rebateValueProportional' => $monthlyRebateValueProportional,
                    'id' => $tmpId++, // helper temp id
                ];
                $calculations[] = $tmpRecord;
            }
        }

        return $this->mergeCalculations($calculations, ['priceListId']);
    }

    private function generateOutputForServiceFee($contract, $groupedData)
    {
        $calculations = [];
        $tmpId = 1;
        foreach ($groupedData as $monthlyData) {
            foreach ($monthlyData['tree'] as $keyPriceListData => $dataPriceListStructure) {
                /** @var PriceList $pricingData */
                $pricingData = $dataPriceListStructure['priceList'];
                /** @var PriceListAndServiceData $priceListAndServiceData */
                foreach ($pricingData->getPriceListAndServiceDatas() as $priceListAndServiceData) {
                    /** @var Service $service */
                    $service = $priceListAndServiceData->getService();
                    $constantValue = $service->getNetPrice();
                    // ommit records if there is no fee value set
                    if (!$constantValue) {
                        continue;
                    }

                    $days = count($dataPriceListStructure['data']);

                    // apply rebates if can
                    $monthlyRebateValue = 0;
//                    if ($allowRebate) {
//                        $monthlyRebateValue = $this->calculateMonthlyNetRebateValue($contract, $pricingData);
//                    }

                    // apply rebate
                    $constantValue = $service->getNetPrice() - $monthlyRebateValue;

                    $tmpRecord = [
                        'title' => $service->getTitle(),
                        'tariff' => '',
                        'area' => '',
                        'deviceId' => null,
                        'consumption' => $monthlyData['calculateMonth'] ? 1 : 0, // it defines months -> canMerge option defines it it will be merged later with other
                        'priceValue' => $constantValue,
                        'netValueBeforeRebate' => $constantValue, // this is constant fee, so all records without splitted ones, are calculated by this constant value
                        'netValueProportionalBeforeRebate' => $constantValue / $monthlyData['monthDays'] * $dataPriceListStructure['daysToCalculateConstantFees'], // this value is calculated proportionally and will be used only in splitted months, do number_format after merge
                        'netValue' => $constantValue, // this is constant fee, so all records without splitted ones, are calculated by this constant value
                        'netValueProportional' => $constantValue / $monthlyData['monthDays'] * $dataPriceListStructure['daysToCalculateConstantFees'], // this value is calculated proportionally and will be used only in splitted months, do number_format after merge
                        'unit' => 'm-c',
                        'vatPercentage' => 23,
                        // additional params to delete after merge
                        'days' => $days,
                        'daysToCalculateConstantFees' => $dataPriceListStructure['daysToCalculateConstantFees'],
                        'priceListId' => $dataPriceListStructure['priceList']->getId(),
                        'subscriptionId' => $pricingData->getId(),
                        'canMerge' => count($monthlyData['tree']) == 1 ? true : false, // if true, that means month was on same parameters without changes, there was not any tariff change or smt.
                        'rebateValue' => $monthlyRebateValue,
    //                    'rebateValueProportional' => $monthlyRebateValueProportional,
                        'id' => $tmpId++, // helper temp id
                    ];
                    $calculations[] = $tmpRecord;
                }
            }
        }

        return $this->mergeCalculations($calculations, ['priceListId', 'title']);
    }

    private function generateOutputForConsumptionOsd($data)
    {
        $groupedData = $this->prepareDataMapForCalculationsOsd($data);

        $calculations = [];
        foreach ($groupedData as $keyOsdData => $dataOsdItem) {
            $treeOsdData = $dataOsdItem['tree'];
            foreach ($treeOsdData as $keyTariff => $dataTariffStructure) {
                /** @var OsdAndOsdDataWithData $pricingData */
                $pricingData = $dataTariffStructure['pricing'];

                $consumption = 0;
                // calculate consumption for pricing
                foreach ($dataTariffStructure['data'] as $dayData) {
                    $consumption += $dayData['consumptionDay'];
                }

                // round consumption
                $consumption = round($consumption);

                $tmpRecord = [
                    'title' => 'Opłata sieciowa zmienna ' . $dataOsdItem['title'],
                    'tariff' => $keyTariff,
                    'area' => '',
                    'deviceId' => null,
                    'consumption' => $consumption,
                    'priceValue' => $pricingData->getFeeVariable(),
                    'netValue' => number_format($pricingData->getFeeVariable() * $consumption, 2, '.', ''),
                    'unit' => 'kWh',
                    'vatPercentage' => 23,
                ];
                $resultSummaryData[] = $tmpRecord;

                $calculations[] = $tmpRecord;
            }
        }

        return $calculations;
    }

    private function groupDateMapByMonths(&$data)
    {
        $groupedByMonths = [];
        foreach ($data as $dayData) {
            $tmp = (clone $dayData['date'])->format('Y-m');
            if (!array_key_exists($tmp, $groupedByMonths)) {
                $fullMonthDays = ((clone $dayData['date'])->modify('last day of this month'))->format('d');
                $groupedByMonths[$tmp] = [
                    'monthDays' => $fullMonthDays,
                    'data' => [],
                ];
            }

            $groupedByMonths[$tmp]['data'][] = $dayData;
        }

        return $groupedByMonths;
    }

    private function prepareDataMapForConstantFeesOsd($groupedDateMapByMonths)
    {
        // apply tariff osd changes
        foreach ($groupedDateMapByMonths as $key => $groupedDateMapByMonth) {
            $groupedDateMapByMonths[$key]['tree'] = [];

            foreach ($groupedDateMapByMonth['data'] as $dayData) {
                if (!array_key_exists($dayData['osdData']->getId(), $groupedDateMapByMonths[$key]['tree'])) {
                    $groupedDateMapByMonths[$key]['tree'][$dayData['osdData']->getId()] = [
                        'osdData' => $dayData['osdData'],
                        'data' => [],
                        'tree' => [],
                        'daysToCalculateConstantFees' => $groupedDateMapByMonth['monthDays'], // initial
                    ];
                }
                $groupedDateMapByMonths[$key]['tree'][$dayData['osdData']->getId()]['data'][] = $dayData;

                // normal tariffs (can change also)
                if (!array_key_exists($dayData['distributionTariff'], $groupedDateMapByMonths[$key]['tree'][$dayData['osdData']->getId()]['tree'])) {
                    $groupedDateMapByMonths[$key]['tree'][$dayData['osdData']->getId()]['tree'][$dayData['distributionTariff']] = [
                        'pricing' => $dayData['osdPricingData'],
                        'data' => [],
                    ];
                }
                $groupedDateMapByMonths[$key]['tree'][$dayData['osdData']->getId()]['tree'][$dayData['distributionTariff']]['data'][] = $dayData;
            }
        }

        // manage and apply days split
        foreach ($groupedDateMapByMonths as $key => $groupedDateMapByMonth) {
            // check only on tariff change, then it will be more data than one in tree (1 means - no change, 2 means one change, 3 means two changes... etc.)
            if (count($groupedDateMapByMonth['tree']) > 1) {
                $index = 1;
                $daysApplied = 0;
                foreach ($groupedDateMapByMonth['tree'] as $groupId => $dataGrouped) {
                    if ($index < count($groupedDateMapByMonth['tree'])) {
                        $daysToApply = count($dataGrouped['data']) + $this->calculateDaysToTheFirstDayOfMonthFromDate($dataGrouped['data'][0]['date']);
                        $daysApplied += $daysToApply;
                        $groupedDateMapByMonths[$key]['tree'][$groupId]['daysToCalculateConstantFees'] = $daysToApply;
                    } else { // last (can be 1st also if no changes)
                        $daysToApply = $groupedDateMapByMonth['monthDays'] - $daysApplied;
                        $daysApplied += $daysToApply;
                        $groupedDateMapByMonths[$key]['tree'][$groupId]['daysToCalculateConstantFees'] = $daysToApply;
                    }
                    $index++;
                }
            }
        }

        return $groupedDateMapByMonths;
    }

    private function calculateDaysToTheFirstDayOfMonthFromDate(\DateTime $date)
    {
        return (int) $date->format('d') - 1;
    }

    private function prepareDataMapForConstantFees($groupedDateMapByMonths)
    {
        // apply tariff osd changes
        foreach ($groupedDateMapByMonths as $key => $groupedDateMapByMonth) {
            $groupedDateMapByMonths[$key]['tree'] = [];

            foreach ($groupedDateMapByMonth['data'] as $dayData) {
                // if it changes it means price list changes
                if (!array_key_exists($dayData['priceList']->getId(), $groupedDateMapByMonths[$key]['tree'])) {
                    $groupedDateMapByMonths[$key]['tree'][$dayData['priceList']->getId()] = [
                        'priceList' => $dayData['priceList'],
                        'data' => [],
                        'tree' => [],
                        'daysToCalculateConstantFees' => $groupedDateMapByMonth['monthDays'], // initial
                    ];
                }
                $groupedDateMapByMonths[$key]['tree'][$dayData['priceList']->getId()]['data'][] = $dayData;

                // if it changes it means subscription changes on price list (tariff changes)
                // subscription conststant fees changes by tariff, for this one create additional level under
                if ($dayData['subscription']) {
                    if (!array_key_exists($dayData['subscription']->getId(), $groupedDateMapByMonths[$key]['tree'][$dayData['priceList']->getId()]['tree'])) {
                        $groupedDateMapByMonths[$key]['tree'][$dayData['priceList']->getId()]['tree'][$dayData['subscription']->getId()] = [
                            'pricing' => $dayData['subscription'],
                            'data' => [],
                        ];
                    }
                    $groupedDateMapByMonths[$key]['tree'][$dayData['priceList']->getId()]['tree'][$dayData['subscription']->getId()]['data'][] = $dayData;
                }
            }
        }

        // manage and apply days split
        foreach ($groupedDateMapByMonths as $key => $groupedDateMapByMonth) {
            // check only on tariff change, then it will be more data than one in tree (1 means - no change, 2 means one change, 3 means two changes... etc.)
            if (count($groupedDateMapByMonth['tree']) > 1) {
                $index = 1;
                $daysApplied = 0;
                foreach ($groupedDateMapByMonth['tree'] as $groupId => $dataGrouped) {
                    if ($index < count($groupedDateMapByMonth['tree'])) {
                        $daysToApply = count($dataGrouped['data']);
                        $daysApplied += $daysToApply;
                        $groupedDateMapByMonths[$key]['tree'][$groupId]['daysToCalculateConstantFees'] = $daysToApply;
                    } else { // last
                        $daysToApply = $groupedDateMapByMonth['monthDays'] - $daysApplied;
                        $daysApplied += $daysToApply;
                        $groupedDateMapByMonths[$key]['tree'][$groupId]['daysToCalculateConstantFees'] = $daysToApply;
                    }
                    $index++;
                }
            }
        }

        return $groupedDateMapByMonths;
    }

    private function generateOutputForConsumptionOsdConstant($groupedDateMapByMonths)
    {
        $preparedDataMapForConstantFeesOsd = $this->prepareDataMapForConstantFeesOsd($groupedDateMapByMonths);

        $calculations = [];
        $tmpId = 1;
        foreach ($preparedDataMapForConstantFeesOsd as $monthlyData) {
            foreach ($monthlyData['tree'] as $keyOsdData => $dataOsdItem) {
                $treeOsdData = $dataOsdItem['tree'];
                foreach ($treeOsdData as $keyTariff => $dataTariffStructure) {

                    /** @var OsdAndOsdDataWithData $pricingData */
                    $pricingData = $dataTariffStructure['pricing'];
                    $constantValue = $pricingData->getFeeConstant();
                    $days = count($dataTariffStructure['data']);

                    $tmpRecord = [
                        'title' => 'Opłata sieciowa stała ' . $dataOsdItem['osdData']->getTitle(),
                        'tariff' => $keyTariff,
                        'area' => '',
                        'deviceId' => null,
                        'consumption' => $monthlyData['calculateMonth'] ? 1 : 0, // it defines months -> canMerge option defines it it will be merged later with other
                        'priceValue' => $constantValue,
                        'netValue' => $constantValue, // this is constant fee, so all records without splitted ones, are calculated by this constant value
                        'netValueProportional' => $constantValue / $monthlyData['monthDays'] * $dataOsdItem['daysToCalculateConstantFees'], // this value is calculated proportionally and will be used only in splitted months, do number_format after merge
                        'unit' => 'm-c',
                        'vatPercentage' => 23,
                        // additional params to delete after merge
                        'days' => $days,
                        'daysToCalculateConstantFees' => $dataOsdItem['daysToCalculateConstantFees'],
                        'canMerge' => count($monthlyData['tree']) == 1 ? true : false, // if true, that means month was on same parameters without changes, there was not any tariff change or smt.
                        'osdDataId' => $dataOsdItem['osdData']->getId(), // helper to know which data merge by this osdData id and tariff
                        'id' => $tmpId++, // helper temp id

                    ];

                    $calculations[] = $tmpRecord;
                }
            }
        }

        return $this->mergeCalculations($calculations, ['osdDataId', 'tariff']);
    }

    private function mergeCalculations(&$calculations, $fieldsThatMustBeEqualToApplyMerge = [])
    {
        $mergedCalculations = [];
        $managedIds = [];

        foreach ($calculations as $calculation) {
            if (in_array($calculation['id'], $managedIds)) {
                continue;
            }

            $actualManagedCalculationData = $calculation;

            if ($calculation['canMerge']) {
                foreach ($calculations as $tmpCalculation) {
                    // ommit same records
                    if ($tmpCalculation['id'] == $calculation['id']) {
                        continue;
                    }

                    if (in_array($tmpCalculation['id'], $managedIds)) {
                        continue;
                    }

                    if (
                        $tmpCalculation['canMerge'] && // if not then ommit (DEFAULT OPTION)
                        $tmpCalculation['consumption'] // if 0, then ommit (DEFAULT OPTION)
                    ) {
                        $ommit = false;
                        foreach ($fieldsThatMustBeEqualToApplyMerge as $checkField) {
                            if ($tmpCalculation[$checkField] != $calculation[$checkField]) {
                                $ommit = true;
                                break;
                            }
                        }

                        if (!$ommit) {
                            $actualManagedCalculationData['consumption'] += $tmpCalculation['consumption'];
                            $actualManagedCalculationData['days'] += $tmpCalculation['days'];
                            $managedIds[] = $tmpCalculation['id'];
                        }
                    }
                }
            } else {
                // in split cases, net value is taken from proportionally calculated value
                $actualManagedCalculationData['netValue'] = $actualManagedCalculationData['netValueProportional'];
                $actualManagedCalculationData['priceValue'] = number_format($actualManagedCalculationData['consumption'] * $actualManagedCalculationData['netValue'], 2, '.', '');
            }

            // multiplied by months number, and formatted
            $actualManagedCalculationData['netValue'] = number_format($actualManagedCalculationData['consumption'] * $actualManagedCalculationData['netValue'], 2, '.', '');


            $mergedCalculations[] = $actualManagedCalculationData;

            // if not added already, add it (if was ommited by some reason)
            if (!in_array($actualManagedCalculationData['id'], $managedIds)) {
                $managedIds[] = $actualManagedCalculationData['id'];
            }
        }

        return $mergedCalculations;
    }

    /**
     * Calculates consumptions for energy and gas, generated summary invoice data
     *
     * @param $records
     * @param ContractEnergyBase $contract
     * @param null $contractTariff
     * @param $osd
     * @param bool $isFirstSettlement
     * @param bool $isProforma
     * @return array
     */
    private function generateOutputForBillingDocument(&$records, $recordsGroupedByAreas, ContractEnergyBase $contract, $osd, $isFirstSettlement = false, $isLastSettlement = false)
    {
        // assign error var
        foreach ($records as $record) {
            $record->error = null;
        }
        $output = [];

        $isEnergy = $contract->getType() == 'ENERGY' ? true : false;

        $dataProviderConstantFees = [
            [
                'title' => 'Opłata abonamentowa',
                'method' => null, // method is static for this case
                'forEnergyOnly' => false,
                'forGasOnly' => true,
                'allowRebate' => true,
                'isSubscription' => true,
            ],
            [
                'title' => 'Opłata handlowa',
                'method' => 'getFeeOhNetValue',
                'forEnergyOnly' => true,
                'forGasOnly' => false,
                'allowRebate' => true,
                'isSubscription' => false,
            ],
            [
                'title' => 'Opłata za Certyfikat OZE',
                'method' => 'getFeeOzeNetValue',
                'forEnergyOnly' => true,
                'forGasOnly' => false,
                'allowRebate' => false,
                'isSubscription' => false,
            ],
            [
                'title' => 'Opłata za GSC',
                'method' => 'getFeeGscNetValue',
                'forEnergyOnly' => true,
                'forGasOnly' => false,
                'allowRebate' => false,
                'isSubscription' => false,
            ],
            [
                'title' => 'Opłata za Pakiet usług dodatkowych',
                'method' => 'getFeeUdNetValue',
                'forEnergyOnly' => false,
                'forGasOnly' => false,
                'allowRebate' => false,
                'isSubscription' => false,
            ]
        ];

        $data = $this->createRecordsDateMap($records, $contract, $isEnergy, $osd);
        foreach ($records as $record) {
            $record->error = null;
        }

        $groupedDateMapByMonths = $this->groupDateMapByMonths($data);
        $this->applyDisableMonthFlags($groupedDateMapByMonths, $isFirstSettlement, $isLastSettlement);

        // calculate seller constant fees (without distribution)
        $preparedDataMapForConstantFees = $this->prepareDataMapForConstantFees($groupedDateMapByMonths);

        foreach ($dataProviderConstantFees as $item) {
            if ($item['forEnergyOnly'] && !$isEnergy) {
                continue;
            }

            if ($item['forGasOnly'] && $isEnergy) {
                continue;
            }

            if ($item['isSubscription']) {
                $constantFeeOutput = $this->generateOutputForConstantFeeSubscription($contract, $preparedDataMapForConstantFees, $item['title'], $item['allowRebate']);
            } else {
                $constantFeeOutput = $this->generateOutputForConstantFee($contract, $preparedDataMapForConstantFees, $item['title'], $item['method'], $item['allowRebate']);
                // additional services
                // todo: all services must be programmed this way to be dynamic
                $constantFeeOutput = $this->generateOutputForConstantFee($contract, $preparedDataMapForConstantFees, $item['title'], $item['method'], $item['allowRebate']);
            }

            $output = array_merge($output, $constantFeeOutput);
        }


        // todo: all services must be programmed this way to be dynamic
        $constantFeeOutput = $this->generateOutputForServiceFee($contract, $preparedDataMapForConstantFees);
        $output = array_merge($output, $constantFeeOutput);


        $consumptionByDevices = [];
        $groupedDataLogs = [];
        foreach ($recordsGroupedByAreas as $recordsGroupedByArea) {
            $data = $this->createRecordsDateMap($recordsGroupedByArea, $contract, $isEnergy, $osd);
            $groupedDateMapByMonths = $this->groupDateMapByMonths($data);
            $this->applyDisableMonthFlags($groupedDateMapByMonths, $isFirstSettlement, $isLastSettlement);

            // calculate consumptions
            if (!$isEnergy) {
                $output = array_merge($output, $this->generateOutputForConsumptionOsd($data));
                $output = array_merge($output, $this->generateOutputForConsumptionOsdConstant($groupedDateMapByMonths));
            }
            $groupedData = $this->prepareDataMapForCalculations($data);
            $groupedDataLogs[] = $groupedData;
            $consumptionByDevices = array_merge($consumptionByDevices, $this->generateOutputForConsumptionByDevices($groupedData));
            $output = array_merge($output, $this->generateOutputForConsumption($groupedData, $isEnergy));
        }

        // append gross value
        $this->calculateOutputGrossValue($output);

        // deletes multiple records with 0 value
        // if record exist with same title and another one is 0 value with same title -> remove it
        if ($output) {
            $outputKeysWithValues = [];
            $newOutput = [];
            $consumptionRecordAreas = [];
            // grab keys with values > 0
            foreach ($output as $item) {
                if ($item['grossValue'] > 0) {
                    $outputKeysWithValues[] = $item['title'];
                    if (isset($item['isConsumptionRecord']) && $item['isConsumptionRecord']) {
                        $consumptionRecordAreas[] = $item['area'];
                    }
                }
            }

            // if 0 value then check if key with value exist, if so, do not add it
            foreach ($output as $item) {
                if ($item['grossValue'] > 0) {
                    $newOutput[] = $item;
                } elseif (
                    isset($item['isConsumptionRecord']) &&
                    $item['isConsumptionRecord'] &&
                    !in_array($item['area'], $consumptionRecordAreas)
                ) {
                    $newOutput[] = $item;
                } elseif (!in_array($item['title'], $outputKeysWithValues)) {
                    $newOutput[] = $item;
                }
            }

            $output = $newOutput;
        }

        return [
            'output' => $output,
            'consumptionByDevices' => $consumptionByDevices,
            'logs' => $groupedDataLogs,
            'rawLogs' => $data,
        ];
    }

    private function calculateOutputGrossValue(&$output)
    {
        // calculate gross value
        foreach ($output as &$calculations) {
            if ($calculations['vatPercentage']) {
                $calculations['grossValue'] = number_format($calculations['netValue'] + $calculations['netValue'] * $calculations['vatPercentage'] / 100, 2, '.', '');
            } else {
                $calculations['grossValue'] = number_format($calculations['netValue'], 2, '.', '');
            }
        }
    }

    private function applyDisableMonthFlags(&$groupedDateMapByMonths, $isFirstSettlement, $isLastSettlement)
    {
        $monthIndex = 1;
        foreach ($groupedDateMapByMonths as &$data) {
            // set initial value to calculate month
            $data['calculateMonth'] = true;

            // first month:
            // disable month calculation if not first settlement and date from > 10
            if ($monthIndex == 1) {
                $checkDay = (clone $data['data'][0]['date'])->format('d');
                if (!$isFirstSettlement && $checkDay > 10) {
                    $data['calculateMonth'] = false;
                }
            }

            // last month:
            if ($monthIndex == count($groupedDateMapByMonths)) {
                // 1 is added, because map of days does not contain last day
                // (problem ex. if statement is < 10 and settlement date to is 10, map of days says that settlement date to is 9 not 10)
                $checkDay = (clone $data['data'][count($data['data']) - 1]['date'])->format('d') + 1;
                if (!$isLastSettlement && $checkDay < 10) {
                    // disable month calculation if not last settlement and date to <= 10
                    $data['calculateMonth'] = false;
                } elseif ($isLastSettlement && $checkDay == 1) {
                    // disable month calculation last settlement and date to == 1
                    $data['calculateMonth'] = false;
                }
            }

            $monthIndex++;
        }
    }

    private function generateOutputForConsumptionByDevices($groupedData)
    {
        // prepare output for devices
        // from - to, tariff -> area -> consumption
        $outputDeviceIds = [];
        foreach ($groupedData as $keyDeviceId => $dataByDeviceId) {
            foreach ($dataByDeviceId['tree'] as $tariffTitle => $dataTariff) {
                foreach ($dataTariff['tree'] as $area => $dataArea) {
                    $consumption = 0;
                    foreach ($dataArea['tree'] as $dataItem) {
                        foreach ($dataItem['data'] as $dataDay) {
                            $consumption += $dataDay['consumptionDay'];
                        }
                    }

                    $outputDeviceIds[] = [
                        'deviceId' => $keyDeviceId,
                        'tariff' => $tariffTitle,
                        'area' => $area,
                        'consumption' => round($consumption),
                        'dateFrom' => $dataArea['data'][0]['date'],
                        'dateTo' => $dataArea['data'][count($dataArea['data']) - 1]['date']->modify('+1 day'), // need to be added 1 day more
                    ];
                }
            }
        }

        return $outputDeviceIds;
    }

    private function managePricingOsd(OsdAndOsdData $recordOsd, $tariff)
    {
        $matchedPricingData = null;

        /** @var OsdAndOsdDataWithData $pricingData */
        foreach ($recordOsd->getOsdAndOsdDataWithDatas() as $pricingData) {
            if ($pricingData->getTariff() == $tariff) {
                $matchedPricingData = $pricingData;
            }
        }

        return $matchedPricingData;
    }

    /**
     * @param $records - all chosen records
     * @param $clientAndContract
     * @return array
     */
    public function prepareData($records, $recordsGroupedByAreas, &$recordsAll, $clientAndContract, $dateFrom = null, $dateTo = null, $isProforma = false)
    {
        $client = $clientAndContract->getClient();
        $this->client = $client;
        /** @var ContractEnergyBase $contract */
        $contract = $clientAndContract->getContract();
        if (!$contract) {
            throw new \Exception('Brak przypisanej umowy.');
        }

        $this->contract = $contract;
        $isEnergy = $contract->getType() == 'ENERGY' ? true : false; // or gas


        // SETS DATE FROM IF NOT EXIST TO FIRST RECORD BILLING PERIOD TO
        if (!$dateFrom && $records) {
            $dateFrom = $records[0]->getBillingPeriodTo();
            $dateFrom = $dateFrom ? (clone $dateFrom)->setTime(0, 0) : null;
        }

        // SETS DATE TO TO LAST RECORD BILLING PERIOD TO IF NOT CHOSEN
        if (!$dateTo && $records) {
            $lastRecordBillingPeriodTo = $records[count($records) - 1]->getBillingPeriodTo();
            $lastRecordBillingPeriodTo ? (clone $lastRecordBillingPeriodTo)->setTime(0, 0) : null;
            if (!$lastRecordBillingPeriodTo) {
                throw new \Exception('Ostatni pobrany rekord nie ma zdefinowanej wymaganej daty odczytu');
            }
            $dateTo = $lastRecordBillingPeriodTo;
            if ($dateTo instanceof \DateTime) {
                $dateTo->setTime(0, 0);
            }
        }


        // CHECK IF ITS REAL SETTLEMENT BY LAST READING RECORD
        $isRealSettlement = false;
        if (!$isProforma && $records) {
            $isRealSettlement =  $records[count($records) - 1]->getReadingType() == 'R' ? true : false;
        }


        $result = [
            'errors' => [],
            'client' => $client,
            'contract' => $contract,
            'billingPeriodFrom' => $dateFrom ?: (new \DateTime())->setTime(0, 0),
            'billingPeriodTo' => $dateTo ?: (new \DateTime())->setTime(0, 0),
            'records' => $records,
            'recordsAll' => $recordsAll,
            'data' => [],
            'rawData' => [],
            'summaryData' => [],
            'consumptionByDevices' => [],
            'isRealSettlement' => $isRealSettlement,
            'additionalData' => [
                'settlementPaymentValue' => null, // to put into static field in settlement
                'settlementPaymentValueIncludedDocuments' => null,
                'isFirstSettlement' => null,
                'isLastSettlement' => null,
                'summaryGrossValue' => null,
            ],
        ];
        if (!$records) {
            return $result;
        }

        // manage osd
        $osd = null;
        if (!$isEnergy) {
            /** @var Osd $osd */
            $osd = $this->osdModel->getRecordByValue($records[0]->getCode());
            if (!$osd) {
                throw new \Exception('Nie znaleziono OSD - nie można przypisać opłaty zmiennej i stałej w zależności od taryfy');
            }
        }


        // manage is first settlement
        $isLastSettlement = false;
        if ($isProforma) { // when proforma -> act like this is first document, count first month etc.
            $isFirstSettlement = true;
        } else {
            $isFirstSettlement = $this->initializer->init($client)->generate()->getMostActiveSettlementDocument() ? false : true;

            // manage is last settlement
            $isLastSettlement = $this->contractModel->hasContractEndStatus($contract);
        }





        // new functionality
        $calculationsData = $this->generateOutputForBillingDocument($records, $recordsGroupedByAreas, $contract, $osd, $isFirstSettlement, $isLastSettlement);
        $result['summaryData'] = $calculationsData['output'];
        $result['consumptionByDevices'] = $calculationsData['consumptionByDevices'];
        $result['data'] = $calculationsData['logs'];
        $result['rawData'] = $calculationsData['rawLogs'];



        // manage document payments, to properly generate settlement document
        // checks only if settlement document
        // for active contracts (status end contract set to false) take only paid value from proforma documents that belongs to this settlement
        // for inactive contracts (status end contract set to true) take value from all active proforma documents and check if there is no empty balance left, if so, add it
        $settlementPaymentValue = 0;
        $settlementSummaryGrossValue = 0;
        $activeProformaTypeDocuments = null;
        $settlementPaymentValueIncludedDocuments = null;
        $documentsContainedInStatement = null;
        if (!$isProforma) { // made calculations only for settlements, proforma documents do not need it
            // calculate settlement gross value
            $settlementSummaryGrossValue = 0;
            foreach ($calculationsData['output'] as $data) {
                $settlementSummaryGrossValue += $data['grossValue'];
            }

            // tariff treat like last settlement functionality
            $treatLikeLastSettlement = false;
            $tariffsTreatLikeLastSettlement = $this->em->getRepository(TariffTreatLikeLastSettlement::class)->findAll();
            if ($tariffsTreatLikeLastSettlement && count($tariffsTreatLikeLastSettlement)) {
                $distributionTariff = $contract->getDistributionTariffByDate($result['billingPeriodTo']);
                if ($distributionTariff) {
                    /** @var TariffTreatLikeLastSettlement $tariffTreatLikeLastSettlement */
                    foreach ($tariffsTreatLikeLastSettlement as $tariffTreatLikeLastSettlement) {
                        if ($distributionTariff->getId() == $tariffTreatLikeLastSettlement->getTariff()->getId()) {
                            $treatLikeLastSettlement = true;
                            break;
                        }
                    }
                }
            }

            $documentsContainedInStatement = $this->initializer->getDocumentsContainedInStatement($dateFrom, $dateTo, $isFirstSettlement, $isLastSettlement);
            if ($treatLikeLastSettlement || $isLastSettlement) { // inactive contract
                $overpaid = $this->initializer->getOverpaidValue();
                $activeProformaTypeDocuments = $this->initializer->getActiveProformaTypeDocuments();
                // take overpaid value and paid value from active proforma type documents (documents that belong to this settlement and further)
                $settlementPaymentValue = $overpaid + $this->initializer->getPaidValueFromDocuments($activeProformaTypeDocuments);
                $settlementPaymentValueIncludedDocuments = $activeProformaTypeDocuments;
            } else { // active contract
                // take summary paid value from proformas that belongs to this settlement
                $settlementPaymentValue = $this->initializer->getDocumentsPaidValue($documentsContainedInStatement);
                $settlementPaymentValueIncludedDocuments = $documentsContainedInStatement;
            }
        }

        $result['additionalData'] = [
            'settlementPaymentValue' => $settlementPaymentValue, // to put into static field in settlement
            'settlementPaymentValueIncludedDocuments' => $settlementPaymentValueIncludedDocuments,
            'settlementIncludedDocuments' => $documentsContainedInStatement,
            'isFirstSettlement' => $isFirstSettlement,
            'isLastSettlement' => $isLastSettlement,
            'summaryGrossValue' => $settlementSummaryGrossValue,
        ];

        // check for errors,
        // adds errors to data if exists
        foreach ($records as $record) {
            if ($record->error) {
                $result['errors'][] = '(#' . $record->getId() . ') '. $record->error;
            }
        }


        return $result;
    }

    public function calculateMonthlyNetRebateValue(ContractEnergyBase $contract, PriceList $priceList)
    {
        $rebateValue = 0;
        if ($contract->getIsRebateElectronicInvoice()) {
            $rebateValue += $priceList->getRebateElectronicInvoiceNetValue() ?: 0;
        }
        if ($contract->getIsRebateMarketingAgreement()) {
            $rebateValue += $priceList->getRebateMarketingAgreementNetValue() ?: 0;
        }
        if ($contract->getIsRebateTimelyPayments()) {
            $rebateValue += $priceList->getRebateTimelyPaymentsNetValue() ?: 0;
        }
        return $rebateValue;
    }

    public function getRecordBefore(&$recordsAll, $chosenRecordDate = null)
    {
        // no records at all, return null
        if (!$recordsAll) {
            return null;
        }

        // not choosen record
        if (!$chosenRecordDate || !($chosenRecordDate instanceof \DateTime)) {
            return null;
        }

        $chosenRecordDate->setTime(0, 0);

        /** @var EnergyData $firstRecordFromAllRecords */
        $firstRecordFromAllRecords = $recordsAll[0];
        $dateFrom = $firstRecordFromAllRecords->getBillingPeriodTo();
        $dateFrom->setTime(0, 0);

        // not choosen record or record choosen is the first record of all records so there is not record before
        if ($dateFrom == $chosenRecordDate) {
            return null;
        }

        $beforeRecord = null;
        /** @var EnergyData $record */
        foreach ($recordsAll as $record) {
            $actualRecord = $record->getBillingPeriodTo();
            if (!$actualRecord) {
                continue;
            }

            $actualRecord->setTime(0, 0);

            if ($beforeRecord && $actualRecord == $chosenRecordDate) {
                return clone $beforeRecord;
            }
            $beforeRecord = clone $record;
        }

        // not found record in given records
        return null;
    }

    public function appendCalculatedConsumption(&$recordsGroupedByAreas)
    {
        $stateStart = 0;

        // SETS FIRST RECORD TO 0 CONSUMPTION
        // always first record from chosen record have to be set to 0 - because we are calculating chosen records,
        // not records from the past

        /** @var EnergyData $record */
        $deviceIdBefore = null;
        foreach ($recordsGroupedByAreas as $area => $areaRecords) {
            // first record check for state start - if have, than set it
            // this functionality does not look back for records that were not chosen
            // only record chosen by date are in use
            if (isset($recordsGroupedByAreas[$area][0]) && $recordsGroupedByAreas[$area][0]->getStateStart()) {
                $stateStart = $recordsGroupedByAreas[$area][0]->getStateStart();
            }

            $index = 0;
            foreach ($areaRecords as $record) {
                $deviceId = $record->getDeviceId();
                $stateEnd = $record->getStateEnd();
                // omit first record from consumption, always first record starts from 0

                if (!$index) {
                    $record->setCalculatedConsumptionM(0);
                    $record->setCalculatedConsumptionKwh(0);
                    $record->setCalculatedStateStart($stateStart);
                } else {
                    // device id change
                    // set new state start from first record of new device
                    if ($deviceIdBefore && $deviceId != $deviceIdBefore) {
                        $stateStart = $record->getStateStart();
                    }

                    $record->setCalculatedConsumptionM($stateEnd - $stateStart);
                    $consumptionLoss = is_numeric($record->getConsumptionLossKwh()) ? $record->getConsumptionLossKwh() : 0;

                    $record->setCalculatedConsumptionKwh(number_format($record->getCalculatedConsumptionM() * $record->getRatio() + $consumptionLoss, 0, '', ''));
                    $record->setCalculatedStateStart($stateStart);
                }

                $stateStart = $stateEnd;
                $deviceIdBefore = $deviceId;
                $index++;
            }
        }
    }

    /**
     * Sometimes OSD does not have tariff column, so tariff cells are filled with #replace# token,
     * to replace with contract tariff
     *
     * @param EnergyData $record
     * @param $contractTariff
     * @return string
     */
    private function manageRecordTariff(EnergyData $record, $defaultTariff)
    {
        $tariff = trim($record->getTariff() == '#replace#' ? $defaultTariff : $record->getTariff());
        return $this->removePostfixFromTariff($tariff);
    }

    private function matchZoneTypeCodeByTariffAndArea(EnergyData $record, $contractTariff)
    {
        $area = $record->getArea();
        $tariff = $this->manageRecordTariff($record, $contractTariff);

        // base table
        $allDay = ['G11', 'G11p', 'C11', 'C11p', 'C11o', 'C21', 'B11', 'B21', 'A21'];
        $dayAndNight = ['G12', 'G12p', 'G12as', 'C12b', 'C12bp', 'C22b', 'B12'];
        $peakAndOffPeak = ['G12r', 'G12w', 'C12a', 'C12ap', 'C22a', 'C22w', 'B22'];
        $morningPeakAfternoonPeakAndRest = ['B23', 'A23', 'G13'];

        // modify base table
        // osd have different set of options - sometimes one area code is in day and night wariants and sometimes in peak and off peak
        // this functionality modify base table individually for osd
        if ($record->getCode() == OsdModel::OPTION_ELECTRICITY_ENERGA) {
            $this->assignNewTariffAreaCodes($tariff, $dayAndNight, ['G12', 'G12w', 'G12as']);
            $this->assignNewTariffAreaCodes($tariff, $peakAndOffPeak, ['G12r']);
        } elseif ($record->getCode() == OsdModel::OPTION_ELECTRICITY_ENEA) {
            $this->assignNewTariffAreaCodes($tariff, $dayAndNight, ['G12', 'G12p']);
            $this->assignNewTariffAreaCodes($tariff, $peakAndOffPeak, ['G12w']);
        } elseif (
            $record->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_SKARZYSKO_KAMIENNA ||
            $record->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_LUBLIN ||
            $record->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_LODZ_MIASTO ||
            $record->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_ZAMOSC ||
            $record->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_BIALYSTOK ||
            $record->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_RZESZOW ||
            $record->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_LODZ_TEREN ||
            $record->getCode() == OsdModel::OPTION_ELECTRICITY_PGE_WARSZAWA
        ) {
            $this->assignNewTariffAreaCodes($tariff, $dayAndNight, ['G12', 'G12as', 'G12n', 'G12w']);
        } elseif ($record->getCode() == OsdModel::OPTION_ELECTRICITY_TAURON) {
            $this->assignNewTariffAreaCodes($tariff, $dayAndNight, ['G12', 'G12as']);
            $this->assignNewTariffAreaCodes($tariff, $peakAndOffPeak, ['G12w']);
        } elseif ($record->getCode() == OsdModel::OPTION_ELECTRICITY_INNOGY) {
            $this->assignNewTariffAreaCodes($tariff, $dayAndNight, ['G12', 'G12w', 'G12as']);
        }

        // settings for tauron
        if ($record->getCode() == OsdModel::OPTION_ELECTRICITY_TAURON) {
            if ($area == 1) {
                return TariffModel::TARIFF_ZONE_ALL_DAY;
            } elseif ($area == 2) {
                return TariffModel::TARIFF_ZONE_PEAK;
            } elseif ($area == 3) {
                return TariffModel::TARIFF_ZONE_OFF_PEAK;
            } elseif ($area == 4) {
                return TariffModel::TARIFF_ZONE_DAY;
            } elseif ($area == 5) {
                return TariffModel::TARIFF_ZONE_NIGHT;
            } elseif ($area == 6) {
                return TariffModel::TARIFF_ZONE_MORNING_PEAK;
            } elseif ($area == 7) {
                return TariffModel::TARIFF_ZONE_AFTERNOON_PEAK;
            } elseif ($area == 8) {
                return TariffModel::TARIFF_ZONE_REMAINING_HOURS_OF_DAY;
            }
            // todo: 9, 10
        }

        if (!$area) {
            return TariffModel::TARIFF_ZONE_ALL_DAY;
        } elseif (in_array($tariff, $allDay)) {
            return TariffModel::TARIFF_ZONE_ALL_DAY;
        } elseif (in_array($tariff, $dayAndNight) && ($area == '1.8.1' || $area == TariffModel::TARIFF_ZONE_DAY)) {
            return TariffModel::TARIFF_ZONE_DAY;
        } elseif (in_array($tariff, $dayAndNight) && ($area == '1.8.2' || $area == TariffModel::TARIFF_ZONE_NIGHT)) {
            return TariffModel::TARIFF_ZONE_NIGHT;
        } elseif (in_array($tariff, $peakAndOffPeak) && ($area == '1.8.1' || $area == TariffModel::TARIFF_ZONE_PEAK)) {
            return TariffModel::TARIFF_ZONE_PEAK;
        } elseif (in_array($tariff, $peakAndOffPeak) && ($area == '1.8.2' || $area == TariffModel::TARIFF_ZONE_OFF_PEAK)) {
            return TariffModel::TARIFF_ZONE_OFF_PEAK;
        } elseif (in_array($tariff, $morningPeakAfternoonPeakAndRest) && ($area == '1.8.1' || $area == TariffModel::TARIFF_ZONE_MORNING_PEAK)) {
            return TariffModel::TARIFF_ZONE_MORNING_PEAK;
        } elseif (in_array($tariff, $morningPeakAfternoonPeakAndRest) && ($area == '1.8.2' || $area == TariffModel::TARIFF_ZONE_AFTERNOON_PEAK)) {
            return TariffModel::TARIFF_ZONE_AFTERNOON_PEAK;
        } elseif (in_array($tariff, $morningPeakAfternoonPeakAndRest) && ($area == '1.8.3' || $area == TariffModel::TARIFF_ZONE_REMAINING_HOURS_OF_DAY)) {
            return TariffModel::TARIFF_ZONE_REMAINING_HOURS_OF_DAY;
        }

        // fallback
        if (!$area) {
            return TariffModel::TARIFF_ZONE_ALL_DAY;
        } elseif (in_array($tariff, $allDay)) {
            return TariffModel::TARIFF_ZONE_ALL_DAY;
        } elseif ($area == TariffModel::TARIFF_ZONE_DAY) {
            return TariffModel::TARIFF_ZONE_DAY;
        } elseif ($area == TariffModel::TARIFF_ZONE_NIGHT) {
            return TariffModel::TARIFF_ZONE_NIGHT;
        } elseif ($area == TariffModel::TARIFF_ZONE_PEAK) {
            return TariffModel::TARIFF_ZONE_PEAK;
        } elseif ($area == TariffModel::TARIFF_ZONE_OFF_PEAK) {
            return TariffModel::TARIFF_ZONE_OFF_PEAK;
        } elseif ($area == TariffModel::TARIFF_ZONE_MORNING_PEAK) {
            return TariffModel::TARIFF_ZONE_MORNING_PEAK;
        } elseif ($area == TariffModel::TARIFF_ZONE_AFTERNOON_PEAK) {
            return TariffModel::TARIFF_ZONE_AFTERNOON_PEAK;
        } elseif ($area == TariffModel::TARIFF_ZONE_REMAINING_HOURS_OF_DAY) {
            return TariffModel::TARIFF_ZONE_REMAINING_HOURS_OF_DAY;
        }

        return TariffModel::getOptionKeyByValue($area);
    }

    private function assignNewTariffAreaCodes($tariff, &$areaCodes, $options)
    {
        $areaCodes = in_array($tariff, $options) ? $options : $areaCodes;
    }

    private function getPriceListDataByTypeCodeAndTariff(PriceList $priceList, $searchTypeCode, $searchTariff)
    {
        $priceListDatas = $priceList->getPriceListDatas();

        /** @var PriceListData $priceListData */
        foreach ($priceListDatas as $priceListData) {
            if ($priceListData->getTariffTypeCode() == $searchTypeCode) {
                $priceListDataAndTariffs = $priceListData->getPriceListDataAndTariffs();
                $found = false;
                /** @var PriceListDataAndTariff $priceListDataAndTariff */
                foreach ($priceListDataAndTariffs as $priceListDataAndTariff) {
                    /** @var Tariff $tariff */
                    $tariff = $priceListDataAndTariff->getTariff();
                    if (mb_strtoupper($tariff->getCode()) == mb_strtoupper($searchTariff)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    continue;
                }

                return $priceListData;
            }
        }
        return null;
    }


    public function getDocumentPath(InvoiceBase $settlement, $absouluteDirPath, $filenamePrefix = '')
    {
        $dirPath = $this->generateDocumentDir($settlement, $absouluteDirPath);

        return $dirPath . '/' . $filenamePrefix . str_replace('/', '-', $settlement->getNumber());
    }

    private function generateDocumentDir(InvoiceBase $settlement, $absouluteDirPath)
    {
        $createdDate = $settlement->getCreatedDate();
        $dirPath = $absouluteDirPath . '/' . $createdDate->format('Y') . '/' . $createdDate->format('m');
        if (file_exists(!$dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        return $dirPath;
    }


}