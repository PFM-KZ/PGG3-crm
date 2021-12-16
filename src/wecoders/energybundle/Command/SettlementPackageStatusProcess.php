<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContract;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Entity\PackageToGenerate;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Entity\PriceListData;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariff;
use Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice;
use Wecoders\EnergyBundle\Entity\SettlementPackage;
use Wecoders\EnergyBundle\Entity\SettlementPackageRecord;
use Wecoders\EnergyBundle\Entity\Tariff;
use Wecoders\EnergyBundle\Event\BillingRecordGeneratedEvent;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\PackageToGenerateModel;
use Wecoders\EnergyBundle\Service\SettlementModel;
use Wecoders\EnergyBundle\Service\SettlementPackageModel;
use Wecoders\EnergyBundle\Service\SettlementPackageRecordModel;

class SettlementPackageStatusProcess extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $settlementPackageModel;

    private $invoiceModel;

    private $initializer;

    private $settlementPackageRecordModel;

    private $settlementModel;

    private $easyAdminModel;

    public function __construct(ContainerInterface $container, EntityManager $em, SettlementModel $settlementModel, SettlementPackageModel $settlementPackageModel, SettlementPackageRecordModel $settlementPackageRecordModel, InvoiceModel $invoiceModel, Initializer $initializer, EasyAdminModel $easyAdminModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->settlementPackageModel = $settlementPackageModel;
        $this->invoiceModel = $invoiceModel;
        $this->initializer = $initializer;
        $this->settlementPackageRecordModel = $settlementPackageRecordModel;
        $this->settlementModel = $settlementModel;
        $this->easyAdminModel = $easyAdminModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:settlement-package-status-process')
            ->setDescription('Process settlement package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('settlement_package_status_process');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();



        /** @var SettlementPackage $settlementPackage */
        $settlementPackage = $this->settlementPackageModel->getSingleRecordByStatus(SettlementPackageModel::STATUS_IN_PROCESS);
        if (!$settlementPackage) {
            dump('No packages with "to process" status.');
            die;
        }

        // Here is status in process,
        // that means firstly documents of contracts must be added
        // next status gonna change
        // and then generating will start (in another command?)
        $summaryCount = count($settlementPackage->getSettlementPackageRecords());
        $checkedSummaryCount = $settlementPackage->getCountCompleted() + $settlementPackage->getCountError();

        $settlementPackageRecords = $em->getRepository('WecodersEnergyBundle:SettlementPackageRecord')->findBy(['settlementPackage' => $settlementPackage]);

        /** @var SettlementPackageRecord $settlementPackageRecord */
        $actualProcessedRecord = null;
        foreach ($settlementPackageRecords as $settlementPackageRecord) {
            if ($settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_IN_PROCESS) {
                $actualProcessedRecord = $settlementPackageRecord;
                break;
            }
        }

        if (!$actualProcessedRecord) {
            // checked all, so change status back to waiting to process, because there can be records with errors to renew process
            // changing back allows to fetch another record
            if ($summaryCount == $checkedSummaryCount) {
                $settlementPackage->setStatus(SettlementPackageModel::STATUS_WAITING_TO_PROCESS);
                $this->em->persist($settlementPackage);
                $this->em->flush($settlementPackage);
            }
            dump('No records to process');
            die;
        }


        $index = 1;
        $em->getConnection()->beginTransaction();
        try {

            // PROCESS
            $client = $actualProcessedRecord->getClient();
            $data = $this->settlementModel->manageAndPrepareData($actualProcessedRecord->getPp(), false, false, $actualProcessedRecord->getDateFrom(), $actualProcessedRecord->getDateTo());


            $clientAndContract = $this->settlementModel->getClientWithContractByPp($actualProcessedRecord->getPp());
            if (!$clientAndContract) {
                throw new \Exception('Nie znaleziono klienta z umową o podanym numerze PP.');
            }


            /** @var ContractEnergyBase $contract */
            $contract = $clientAndContract->getContract();


            if ($data['isRealSettlement']) {
                $config = $this->easyAdminModel->getEntityConfigByEntityName('InvoiceSettlementEnergy');
            } else {
                $config = $this->easyAdminModel->getEntityConfigByEntityName('InvoiceEstimatedSettlementEnergy');
            }


            /** @var PriceList $priceList */
            $priceList = $contract->getPriceListByDate($actualProcessedRecord->getDateFrom());
            if (!$priceList) {
                throw new \Exception('Nie znaleziono cennika na podstawie daty.');
            }

            $invoice = $this->settlementModel->generateInvoiceRecordFromData($data, $config, $contract->getType(), $settlementPackage->getCreatedDate(), (clone $settlementPackage->getCreatedDate())->modify('+' . $priceList->getDateOfPaymentDays() . ' days'));
            if ($invoice instanceof InvoiceSettlement) {
                $invoice = $em->getRepository('WecodersEnergyBundle:InvoiceSettlement')->find($invoice->getId());
            } else {
                $invoice = $em->getRepository('WecodersEnergyBundle:InvoiceEstimatedSettlement')->find($invoice->getId());
            }

            $actualProcessedRecord = $em->getRepository('WecodersEnergyBundle:SettlementPackageRecord')->find($actualProcessedRecord->getId());
            if ($data['isRealSettlement']) {
                $actualProcessedRecord->setInvoiceSettlement($invoice);
            } else {
                $actualProcessedRecord->setInvoiceEstimatedSettlement($invoice);
            }
            $actualProcessedRecord->setStatus(SettlementPackageRecordModel::STATUS_WAITING_TO_GENERATE);

            $em->persist($actualProcessedRecord);
            $em->flush();

            $em->getConnection()->commit();
            // update client invoices paid state
            $billingDocumentsObject = $this->initializer->init($client)->generate();
            $billingDocumentsObject->updateDocumentsIsPaidState();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            /** @var SettlementPackageRecord $reFetchedSettlementPackageRecord*/
            $reFetchedSettlementPackageRecord = $this->settlementPackageRecordModel->getRecord($actualProcessedRecord->getId());
            $reFetchedSettlementPackageRecord->setErrorMessage($e->getMessage() . ' - on line: ' . $e->getLine());
            $reFetchedSettlementPackageRecord->setStatus(SettlementPackageRecordModel::STATUS_PROCESS_ERROR);
            $em->persist($reFetchedSettlementPackageRecord);
            $em->flush();
        }





        $em->clear();
        $em->getConnection()->close();
        dump('Success');
        // release lock, so command can be used again
        $lock->release();


        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:settlement-package-status-process');
    }

    private function getCurrentEnergyPricesByPriceListAndTariff(PriceList $priceList, Tariff $tariff, \DateTime $date, $contractType)
    {
        // check if tariff exist in price list in current year
        /** @var PriceListData $data */
        $data = $priceList->getPriceListDatas();
        if (!$data) {
            throw new \Exception('Price list data is empty');
        }

        /** @var PriceListData $priceListData */
        $energyPrices = [];
        foreach ($data as $priceListData) {
            $foundTariffSoCanCheckForCurrentYearIfExist = false;

            $tariffTypeCode = $priceListData->getTariffTypeCode();
            if (!$tariffTypeCode) {
                throw new \Exception('Price list data tariff type code is empty: ' . $priceList);
            }

            /** @var PriceListDataAndTariff $priceListDataAndTariffs */
            $priceListDataAndTariffs = $priceListData->getPriceListDataAndTariffs();
            if (!$priceListDataAndTariffs) {
                throw new \Exception('Price list data and tariffs are empty: '  . $priceList);
            }

            /** @var PriceListDataAndTariff $priceListDataAndTariff */
            foreach ($priceListDataAndTariffs as $priceListDataAndTariff) {
                /** @var Tariff $itemTariff */
                $itemTariff = $priceListDataAndTariff->getTariff();
                if (!$itemTariff) {
                    continue;
                }
                if ($itemTariff->getId() == $tariff->getId()) {
                    $foundTariffSoCanCheckForCurrentYearIfExist = true;
                    break;
                }
            }

            if ($foundTariffSoCanCheckForCurrentYearIfExist) {
                $priceListDataAndYearWithPrices = $priceListData->getPriceListDataAndYearWithPrices();
                if (!$priceListDataAndYearWithPrices) {
                    throw new \Exception('Price list data and year with prices are empty: ' . $priceList);
                }

                // search if year match with current year
                $date->format('Y');
                // if pricing for current year is not specified, it takes last pricing data
                $lastEnergyPricing = null;

                /** @var PriceListDataAndYearWithPrice $priceListDataAndYearWithPrice */
                foreach ($priceListDataAndYearWithPrices as $priceListDataAndYearWithPrice) {
                    $year = $priceListDataAndYearWithPrice->getYear();
                    $grossValue = $priceListDataAndYearWithPrice->getGrossValue();
                    $netValue = $priceListDataAndYearWithPrice->getNetValue();

                    if ($contractType == 'ENERGY') {
                        if (!is_numeric($grossValue) || !is_numeric($netValue)) {
                            throw new \Exception('Price list data and year with prices are not set properly: ' . $priceList);
                        }

                        $lastEnergyPricing = [
                            'typeCode' => $tariffTypeCode,
                            'netValue' => $netValue,
                            'grossValue' => $grossValue,
                        ];

                        if (!$year) {
                            $energyPrices[] = [
                                'typeCode' => $tariffTypeCode,
                                'netValue' => $netValue,
                                'grossValue' => $grossValue,
                            ];
                            break;
                        }

                        if ($year == $date->format('Y')) {
                            $energyPrices[] = [
                                'typeCode' => $tariffTypeCode,
                                'netValue' => $netValue,
                                'grossValue' => $grossValue,
                            ];
                            break;
                        }
                    } else {
                        $energyPrices[] = [
                            'typeCode' => $tariffTypeCode,
                            'netValue' => $netValue,
                            'grossValue' => $grossValue,
                        ];
                        break;
                    }
                }

                if (!count($energyPrices) && $lastEnergyPricing) {
                    $energyPrices[] = $lastEnergyPricing;
                    $lastEnergyPricing = null;
                }
            }
        }
        if (!count($energyPrices)) {
            throw new \Exception('Energy prices not found for current price list: ' . $priceList . ' and tariff: ' . $tariff);
        }

        return $energyPrices;
    }

}