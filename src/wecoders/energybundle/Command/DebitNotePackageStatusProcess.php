<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContract;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\DebitNote;
use Wecoders\EnergyBundle\Entity\DebitNotePackage;
use Wecoders\EnergyBundle\Entity\DebitNotePackageRecord;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\SettlementModel;
use Wecoders\EnergyBundle\Service\DebitNotePackageModel;
use Wecoders\EnergyBundle\Service\DebitNotePackageRecordModel;

class DebitNotePackageStatusProcess extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $debitNotePackageModel;

    private $invoiceModel;

    private $initializer;

    private $debitNotePackageRecordModel;

    private $settlementModel;

    private $easyAdminModel;

    public function __construct(
        ContainerInterface $container,
        EntityManager $em,
        SettlementModel $settlementModel,
        DebitNotePackageModel $debitNotePackageModel,
        DebitNotePackageRecordModel $debitNotePackageRecordModel,
        InvoiceModel $invoiceModel,
        Initializer $initializer,
        EasyAdminModel $easyAdminModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->debitNotePackageModel = $debitNotePackageModel;
        $this->invoiceModel = $invoiceModel;
        $this->initializer = $initializer;
        $this->debitNotePackageRecordModel = $debitNotePackageRecordModel;
        $this->settlementModel = $settlementModel;
        $this->easyAdminModel = $easyAdminModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:debit-note-package-status-process')
            ->setDescription('Process debit note package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('debit_note_package_status_process');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();



        /** @var DebitNotePackage $package */
        $package = $this->debitNotePackageModel->getSingleRecordByStatus(DebitNotePackageModel::STATUS_IN_PROCESS);
        if (!$package) {
            dump('No packages with "to process" status.');
            die;
        }

        // Here is status in process,
        // that means firstly documents of contracts must be added
        // next status gonna change
        // and then generating will start (in another command?)
        $summaryCount = count($package->getPackageRecords());
        $checkedSummaryCount = $package->getCountCompleted() + $package->getCountError();

        /** @var DebitNotePackageRecord $packageRecord */
        $actualProcessedRecord = null;
        foreach ($package->getPackageRecords() as $packageRecord) {
            if ($packageRecord->getStatus() == DebitNotePackageRecordModel::STATUS_IN_PROCESS) {
                $actualProcessedRecord = $packageRecord;
                break;
            }
        }

        if (!$actualProcessedRecord) {
            // checked all, so change status back to waiting to process, because there can be records with errors to renew process
            // changing back allows to fetch another record
            if ($summaryCount == $checkedSummaryCount) {
                $package->setStatus(DebitNotePackageModel::STATUS_WAITING_TO_PROCESS);
                $this->em->persist($package);
                $this->em->flush($package);
            }
            dump('No records to process');
            die;
        }

        $em->getConnection()->beginTransaction();
        try {
            // PROCESS
            /** @var Client $client */
            $client = $actualProcessedRecord->getClient();

            $document = new DebitNote();

            $document->setClient($client);
            $document->setCreatedDate($package->getCreatedDate());
            $document->setDateOfPayment((clone $package->getCreatedDate())->modify('+21 days'));
            $document->setContractNumber($actualProcessedRecord->getContractNumber());

            $document->setBadgeId($client->getAccountNumberIdentifier()->getNumber());
            $document->setClientAccountNumber($client->getBankAccountNumber());

            $document->setClientName($client->getName());
            $document->setClientSurname($client->getSurname());
            $document->setClientZipCode($client->getToCorrespondenceZipCode());
            $document->setClientCity($client->getToCorrespondenceCity());
            $document->setClientStreet($client->getToCorrespondenceStreet());
            $document->setClientHouseNr($client->getToCorrespondenceHouseNr());
            $document->setClientApartmentNr($client->getToCorrespondenceApartmentNr());
            $document->setClientPostOffice($client->getToCorrespondencePostOffice());

            $document->setPenaltyAmountPerMonth($actualProcessedRecord->getPenaltyAmountPerMonth());
            $document->setContractSignDate($actualProcessedRecord->getContractSignDate());
            $document->setContractFromDate($actualProcessedRecord->getContractFromDate());
            $document->setContractToDate($actualProcessedRecord->getContractToDate());
            $document->setMonthsNumber($actualProcessedRecord->getMonthsNumber());
            $document->setSummaryGrossValue($actualProcessedRecord->getSummaryGrossValue());

            $document->setDocumentTemplate($actualProcessedRecord->getDocumentTemplate());

            $em->persist($document);

            $actualProcessedRecord->setDocument($document);
            $actualProcessedRecord->setStatus(DebitNotePackageRecordModel::STATUS_WAITING_TO_GENERATE);

            $em->persist($actualProcessedRecord);
            $em->flush();

            $em->getConnection()->commit();
            // update client invoices paid state
            $billingDocumentsObject = $this->initializer->init($client)->generate();
            $billingDocumentsObject->updateDocumentsIsPaidState();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            /** @var DebitNotePackageRecord $reFetchedPackageRecord*/
            $reFetchedPackageRecord = $this->debitNotePackageRecordModel->getRecord($actualProcessedRecord->getId());
            $reFetchedPackageRecord->setErrorMessage($e->getMessage() . ' - on line: ' . $e->getLine());
            $reFetchedPackageRecord->setStatus(DebitNotePackageRecordModel::STATUS_PROCESS_ERROR);
            $em->persist($reFetchedPackageRecord);
            $em->flush();
        }

        $em->clear();
        $em->getConnection()->close();
        dump('Success');
        // release lock, so command can be used again
        $lock->release();

        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:debit-note-package-status-process');
    }

}