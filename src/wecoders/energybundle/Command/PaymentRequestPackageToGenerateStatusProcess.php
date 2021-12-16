<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractInterface;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\DebitNote;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;
use Wecoders\EnergyBundle\Entity\PackageToGenerate;
use Wecoders\EnergyBundle\Entity\PaymentRequest;
use Wecoders\EnergyBundle\Entity\PaymentRequestAndDocument;
use Wecoders\EnergyBundle\Entity\PaymentRequestPackageToGenerate;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Entity\PriceListData;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariff;
use Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice;
use Wecoders\EnergyBundle\Entity\Tariff;
use Wecoders\EnergyBundle\Event\BillingRecordGeneratedEvent;
use Wecoders\EnergyBundle\Service\DocumentBankAccountChangeModel;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\PackageToGenerateModel;
use Wecoders\EnergyBundle\Service\PaymentRequestModel;
use Wecoders\EnergyBundle\Service\PaymentRequestPackageToGenerateModel;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;

class PaymentRequestPackageToGenerateStatusProcess extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $packageToGenerateModel;

    private $paymentRequestModel;

    private $invoiceTemplateModel;

    private $initializer;

    private $companyModel;

    private $clientModel;

    private $documentBankAccountChangeModel;

    public function __construct(
        ContainerInterface $container,
        EntityManager $em,
        PaymentRequestPackageToGenerateModel $packageToGenerateModel,
        PaymentRequestModel $paymentRequestModel,
        InvoiceTemplateModel $invoiceTemplateModel,
        Initializer $initializer,
        CompanyModel $companyModel,
        ClientModel $clientModel,
        DocumentBankAccountChangeModel $documentBankAccountChangeModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->packageToGenerateModel = $packageToGenerateModel;
        $this->paymentRequestModel = $paymentRequestModel;
        $this->invoiceTemplateModel = $invoiceTemplateModel;
        $this->initializer = $initializer;
        $this->companyModel = $companyModel;
        $this->clientModel = $clientModel;
        $this->documentBankAccountChangeModel = $documentBankAccountChangeModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:payment-request-package-to-generate-status-process')
            ->setDescription('Process package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('payment_request_package_to_generate_status_process');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();



        /** @var PaymentRequestPackageToGenerate $packageToGenerate */
        $packageToGenerate = $this->packageToGenerateModel->getSingleRecordByStatus(PackageToGenerateModel::STATUS_IN_PROCESS);
        if (!$packageToGenerate) {
            dump('No packages with "to process" status.');
            die;
        }

        // Here is status in process,
        // that means firstly documents of objects must be added
        // next status gonna change
        // and then generating will start (in another command?)

        $objectsIds = $packageToGenerate->getObjectIds();
        $checkedContractsIds = $packageToGenerate->getCheckedObjectIds();
        $objectsNotCheckedIds = array_values(array_diff($objectsIds, $checkedContractsIds));
        $objectToCheckId = count($objectsNotCheckedIds) ? $objectsNotCheckedIds[0] : null;
        if (!$objectToCheckId) {
            // all objects were checked, so all records documents are ready to be generated
            // this is the moment to change status to generate documents
            $packageToGenerate->setStatus(PackageToGenerateModel::STATUS_WAITING_TO_GENERATE);
            $em->persist($packageToGenerate);
            $em->flush();
            dump('Status changed to generate');
            dump('Success');
            die;
        }


        // gets object by id
        /** @var Client $object */
        $object = $em->getRepository('GCRMCRMBundle:Client')->find($objectToCheckId);

        if (!$object) {
            throw new \Exception('Object client from package to generate does not exist: ' . $objectToCheckId);
        }


        /** @var InvoiceTemplate $documentTemplate */
        $documentTemplate = $this->invoiceTemplateModel->getTemplateRecordByCode('payment_request');
        if (!$documentTemplate) {
            throw new \Exception('Document template not found');
        }

        $initializer = $this->initializer->init($object)->generate();
        $initializerStructure = $initializer->getStructure();

        $documents = $initializer->getRecordsWithOverduePayment($initializerStructure);

        $contract = null;
        /** @var ContractEnergyBase $contract */
        /** @var ClientAndContractInterface $clientAndContract */
        foreach ($object->getClientAndGasContracts() as $clientAndContract) {
            if ($clientAndContract->getContract()) {
                $contract = $clientAndContract->getContract();
            }
        }

        /** @var ClientAndContractInterface $clientAndContract */
        foreach ($object->getClientAndEnergyContracts() as $clientAndContract) {
            if ($clientAndContract->getContract()) {
                $contract = $clientAndContract->getContract();
            }
        }




        // generates document
        $em->getConnection()->beginTransaction();
        try {
            if (!$contract) {
                throw new \Exception('Contract not found for client: #' . $object->getId() . ' ' . $object->getBadgeId());
            }

            $paymentRequest = new PaymentRequest();
            $paymentRequest->setCreatedDate($packageToGenerate->getCreatedDate());
            $paymentRequest->setDateOfPayment((clone $packageToGenerate->getCreatedDate())->modify('+14 days'));

            $paymentRequest->setClient($object);
            $paymentRequest->setBadgeId($object->getBadgeId());

            $paymentRequest->setClientAccountNumber($object->getBankAccountNumber());

            $paymentRequest->setContractNumber($contract->getContractNumber());
            $paymentRequest->setPpZipCode($contract->getPpZipCode());
            $paymentRequest->setPpPostOffice($contract->getPpPostOffice());
            $paymentRequest->setPpCity($contract->getPpCity());
            $paymentRequest->setPpStreet($contract->getPpStreet());
            $paymentRequest->setPpHouseNr($contract->getPpHouseNr());
            $paymentRequest->setPpApartmentNr($contract->getPpApartmentNr());

            $paymentRequest->setClientName($object->getName());
            $paymentRequest->setClientSurname($object->getSurname());
            $paymentRequest->setClientPesel($object->getPesel());
            $paymentRequest->setClientNip($object->getNip());
            $paymentRequest->setClientZipCode($object->getToCorrespondenceZipCode());
            $paymentRequest->setClientPostOffice($object->getToCorrespondencePostOffice());
            $paymentRequest->setClientCity($object->getToCorrespondenceCity());
            $paymentRequest->setClientStreet($object->getToCorrespondenceStreet());
            $paymentRequest->setClientHouseNr($object->getToCorrespondenceHouseNr());
            $paymentRequest->setClientApartmentNr($object->getToCorrespondenceApartmentNr());

            $paymentRequest->setIsPaid(false);
            $paymentRequest->setSummaryGrossValue($initializerStructure['balance']['toPay']);
            $paymentRequest->setDocumentTemplate($documentTemplate);

            /** @var InvoiceInterface $document */
            foreach ($documents as $document) {
                $paymentRequestAndDocument = new PaymentRequestAndDocument();
                $paymentRequestAndDocument->setPaymentRequest($paymentRequest);
                if ($document instanceof DebitNote) {
                    $paymentRequestAndDocument->setDocumentNumber('Nota obciążeniowa');
                    $paymentRequestAndDocument->setBillingPeriod('-');
                } else {
                    $paymentRequestAndDocument->setDocumentNumber($document->getNumber());
                    $paymentRequestAndDocument->setBillingPeriod($document->getBillingPeriodFrom()->format('Y-m'));
                    $paymentRequestAndDocument->setBillingPeriodFrom($document->getBillingPeriodFrom());
                    $paymentRequestAndDocument->setBillingPeriodTo($document->getBillingPeriodTo());
                }

                // gets overdue date of payment
                $dateStart = $document->getDateOfPayment();
                $dateStart = $dateStart->setTime(0,0);
                $dateEnd = new \DateTime();

                if ($dateStart < $dateEnd) {
                    $diff = $dateStart->diff($dateEnd);
                    $diffDays = $diff->days;
                } else {
                    $diffDays = 0;
                }

                $paymentRequestAndDocument->setDaysOverdue($diffDays);
                $paymentRequestAndDocument->setToPay($document->getSummaryGrossValue() - $document->getPaidValue());

                $paymentRequest->addPaymentRequestAndDocument($paymentRequestAndDocument);
            }

            $em->persist($paymentRequest);
            $em->flush();

            // adds document id as complete to package
            $packageToGenerate->addDocumentId($paymentRequest->getId());
            $em->persist($packageToGenerate);
            $em->flush($packageToGenerate);

            $packageToGenerate->addCheckedObjectId($object->getId());
            $em->persist($packageToGenerate);
            $em->flush();

            // update
            $billingRecordGeneratedEvent = new BillingRecordGeneratedEvent($paymentRequest);
            $this->container->get('event_dispatcher')->dispatch('billing_record.post_persist', $billingRecordGeneratedEvent);

            $em->getConnection()->commit();
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
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:payment-request-package-to-generate-status-process');
    }

}