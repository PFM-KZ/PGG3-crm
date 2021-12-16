<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContract;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\Distributor;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\DistributorModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\DocumentBankAccountChange;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;
use Wecoders\EnergyBundle\Entity\PackageToGenerate;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Entity\PriceListData;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariff;
use Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice;
use Wecoders\EnergyBundle\Entity\Tariff;
use Wecoders\EnergyBundle\Event\BillingRecordGeneratedEvent;
use Wecoders\EnergyBundle\Service\DocumentBankAccountChangeModel;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\PackageToGenerateModel;
use Wecoders\EnergyBundle\Service\PriceListModel;
use Wecoders\EnergyBundle\Service\TariffModel;

class PackageToGenerateStatusProcess extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $packageToGenerateModel;

    private $invoiceModel;

    private $initializer;

    private $documentBankAccountChangeModel;

    private $distributorModel;

    private $priceListModel;

    public function __construct(
        ContainerInterface $container,
        EntityManager $em,
        PackageToGenerateModel $packageToGenerateModel,
        InvoiceModel $invoiceModel,
        Initializer $initializer,
        DocumentBankAccountChangeModel $documentBankAccountChangeModel,
        DistributorModel $distributorModel,
        PriceListModel $priceListModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->packageToGenerateModel = $packageToGenerateModel;
        $this->invoiceModel = $invoiceModel;
        $this->initializer = $initializer;
        $this->documentBankAccountChangeModel = $documentBankAccountChangeModel;
        $this->distributorModel = $distributorModel;
        $this->priceListModel = $priceListModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:package-to-generate-status-process')
            ->setDescription('Process package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('package_to_generate_status_process');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();



        /** @var PackageToGenerate $packageToGenerate */
        $packageToGenerate = $this->packageToGenerateModel->getSingleRecordByStatus(PackageToGenerateModel::STATUS_IN_PROCESS);
        if (!$packageToGenerate) {
            dump('No packages with "to process" status.');
            die;
        }

        // Here is status in process,
        // that means firstly documents of contracts must be added
        // next status gonna change
        // and then generating will start (in another command?)
        $contractsIds = $packageToGenerate->getContractIds();
        $checkedContractsIds = $packageToGenerate->getCheckedContractIds();
        $contractsNotCheckedIds = array_values(array_diff($contractsIds, $checkedContractsIds));
        $contractToCheckId = count($contractsNotCheckedIds) ? $contractsNotCheckedIds[0] : null;
        if (!$contractToCheckId) {
            // all contracts were checked, so all records documents are ready to be generated
            // this is the moment to change status to generate documents
            $packageToGenerate->setStatus(PackageToGenerateModel::STATUS_WAITING_TO_GENERATE);
            $em->persist($packageToGenerate);
            $em->flush();
            dump('Status changed to generate');
            dump('Success');
            die;
        }

        $index = 1;
        $em->getConnection()->beginTransaction();
        try {
            // PREPARE
            // gets contract by id
            $contractsEntity = 'GCRMCRMBundle:' . ($packageToGenerate->getContractType() == 'GAS' ? 'ContractGas' : 'ContractEnergy');
            /** @var ContractEnergyBase $contract */
            $contract = $em->getRepository($contractsEntity)->find($contractToCheckId);

            if (!$contract) {
                throw new \Exception('Contract (' . $contractsEntity . ') from package to generate does not exist: #' . $contractToCheckId);
            }

            // get client by contract
            $clientAndContractEntity = 'GCRMCRMBundle:' . ($packageToGenerate->getContractType() == 'GAS' ? 'ClientAndContractGas' : 'ClientAndContractEnergy');

            $clientAndContracts = $em->getRepository($clientAndContractEntity)->findBy(['contract' => $contract]);
            if (!$clientAndContracts) {
                throw new \Exception('Contract (' . $contractsEntity . ') from package to generate does not have client assigned to it: #' . $contractToCheckId . ' ' . $contract->getContractNumber());
            }

            if ($clientAndContracts && count($clientAndContracts) > 1) {
                /** @var ClientAndContract $tmpClientAndContract */
                $tmpMergeClients = [];
                foreach ($clientAndContracts as $tmpClientAndContract) {
                    /** @var Client $tmpClient */
                    $tmpClient = $tmpClientAndContract->getClient();
                    $pesel = $tmpClient->getPesel();
                    $tmpMergeClients[] = $pesel;
                }
                throw new \Exception('Contract (' . $contractsEntity . ') from package to generate have more than one client assigned to it: #' . $contractToCheckId . ' ' . $contract->getContractNumber() . ' clients by pesel: ' . implode(', ', $tmpMergeClients));
            }

            $clientAndContract = $clientAndContracts[0];
            /** @var Client $client */
            $client = $clientAndContract->getClient();
            if (!$client) {
                throw new \Exception('Contract (' . $contractsEntity . ') from package to generate does not have client assigned to it: #' . $contractToCheckId . ' ' . $contract->getContractNumber());
            }








            // generates document
            // sets billing period from at 1st day of month
            // sets billing period to at last day of month
            // sets date of payment on first invoice +14 day from now and on another 15 day of current month
            if (!$contract->getBeforeInvoicingPeriod()) {
                $billingPeriodFrom = clone $contract->getContractFromDate();
            } else {
                $billingPeriodFrom = clone $contract->getBeforeInvoicingPeriod();
            }
            $billingPeriodFrom->setTime(0, 0);

            // check if price list exist
            /** @var PriceList $priceList */
            $priceList = $contract->getPriceListByDate($billingPeriodFrom);
            if (!$priceList) {
                throw new \Exception('Contract (' . $contractsEntity . ') from package to generate does not have price list for current time: #' . $contractToCheckId . ' ' . $contract->getContractNumber());
            }

            $billingPeriodTo = clone $billingPeriodFrom;
            $billingPeriodTo->modify('last day of this month');
            $billingPeriodFrom->modify('first day of this month');
            $createdDate = $packageToGenerate->getCreatedDate();
            if (!$createdDate) {
                $createdDate = new \DateTime();
                $createdDate->setTime(0, 0, 0);
            }

            if ($createdDate < $billingPeriodFrom) {
                $dateOfPayment = clone $billingPeriodFrom;
            } else {
                $dateOfPayment = clone $createdDate;
            }

            $dateOfPayment->modify('+' . $priceList->getDateOfPaymentDays() . ' days');




            // check if tariff exist
            /** @var Tariff $tariff */
            $tariff = $contract->getSellerTariffByDate($billingPeriodFrom);
            if (!$tariff) {
                throw new \Exception('Contract (' . $contractsEntity . ') from package to generate does not have tariff: ' . $contractToCheckId . ' ' . $contract->getContractNumber());
            }

            if (!$tariff->getInvoicingPeriodInMonths()) {
                throw new \Exception('Tariff invoicing period in months is not choosen: #' . $tariff->getId() . ' ' . $tariff->getCode());
            }





            // PROCESS


            for ($i = 0; $i < $tariff->getInvoicingPeriodInMonths(); $i++) {
                $energyPrices = $this->priceListModel->getCurrentEnergyPricesByPriceListAndTariff($priceList, $tariff, $billingPeriodFrom, $contract->getType());
                $energyPrices = $this->distributorModel->filterEnergyPricesByDistributorTableData($energyPrices, $tariff->getCode(), $contract->getDistributorObject());
                $invoiceProforma = $this->invoiceModel->generateRecordInvoiceProforma($clientAndContract, $energyPrices, $billingPeriodFrom, $billingPeriodTo, $createdDate, $dateOfPayment);

                $em->persist($invoiceProforma);
                $em->flush();

                // Dispatching the event
                $billingRecordGeneratedEvent = new BillingRecordGeneratedEvent($invoiceProforma);
                $this->container->get('event_dispatcher')->dispatch('billing_record.post_persist_single_document_actions', $billingRecordGeneratedEvent);

                // adds document id as complete to package
                $packageToGenerate->addDocumentId($invoiceProforma->getId());
                $em->persist($packageToGenerate);
                $em->flush($packageToGenerate);

                // sets next invoice dates data
                $billingPeriodFrom = clone $billingPeriodFrom;
                $billingPeriodFrom->modify('+1 month');
                $billingPeriodFrom->modify('first day of this month');

                $billingPeriodTo = clone $billingPeriodFrom;
                $billingPeriodTo->modify('last day of this month');

                if ($createdDate < $billingPeriodFrom) {
                    $dateOfPayment = clone $billingPeriodFrom;
                } else {
                    $dateOfPayment = clone $createdDate;
                }
                $dateOfPayment->modify('+' . $priceList->getDateOfPaymentDays() . ' days');

                $priceList = $contract->getPriceListByDate(clone $billingPeriodFrom);

                dump($index);
                $index++;
            }

            $packageToGenerate->addCheckedContractId($contract->getId());
            $em->persist($packageToGenerate);
            $em->flush();

            $em->getConnection()->commit();

            // update client invoices paid state
            $billingDocumentsObject = $this->initializer->init($client)->generate();
            $billingDocumentsObject->updateDocumentsIsPaidState();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            /** @var PackageToGenerate $reFetchedPackageToGenerate */
            $reFetchedPackageToGenerate = $this->packageToGenerateModel->getRecord($packageToGenerate->getId());
            $reFetchedPackageToGenerate->setErrorMessage($e->getMessage() . ' - on line: ' . $e->getLine());
            $reFetchedPackageToGenerate->setStatus(PackageToGenerateModel::STATUS_PROCESS_ERROR);
            $em->persist($reFetchedPackageToGenerate);
            $em->flush();
        }




        $em->clear();
        $em->getConnection()->close();
        dump('Success');
        // release lock, so command can be used again
        $lock->release();


        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:package-to-generate-status-process');
    }

}