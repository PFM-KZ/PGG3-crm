<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\Balance;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\PaymentModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\InvoiceBundle\Service\InvoiceData;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;
use Wecoders\InvoiceBundle\Service\NumberModel;

class GenerateInvoicesForMarkedClientsCommand extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $numberModel;

    private $companyModel;

    private $clientModel;

    private $paymentModel;

    private $invoiceData;

    private $invoiceModel;

    private $invoiceTemplateModel;

    private $easyAdminModel;

    private $energyBundleInvoiceModel;

    public function __construct(
        EntityManager $em,
        ContainerInterface $container,
        NumberModel $numberModel,
        CompanyModel $companyModel,
        ClientModel $clientModel,
        PaymentModel $paymentModel,
        InvoiceTemplateModel $invoiceTemplateModel,
        InvoiceData $invoiceData,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceModel,
        EasyAdminModel $easyAdminModel,
        InvoiceModel $energyBundleInvoiceModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->numberModel = $numberModel;
        $this->companyModel = $companyModel;
        $this->clientModel = $clientModel;
        $this->paymentModel = $paymentModel;
        $this->invoiceTemplateModel = $invoiceTemplateModel;
        $this->invoiceData = $invoiceData;
        $this->invoiceModel = $invoiceModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->energyBundleInvoiceModel = $energyBundleInvoiceModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:generate-invoices-for-marked-clients')
            ->setDescription('Generate invoices for marked clients.');
    }

    private function getClients()
    {
        $qb = $this->em->createQueryBuilder();

        $q = $qb->select(['a'])
            ->from('GCRMCRMBundle:Client', 'a')
            ->where('a.isMarkedToGenerateInvoice = :isMarkedToGenerateInvoice')
            ->andWhere('a.isInvoiceGenerated = :isInvoiceGenerated')
            ->setMaxResults(200)
            ->setParameters([
                'isMarkedToGenerateInvoice' => true,
                'isInvoiceGenerated' => false
            ])
            ->getQuery()
        ;

        return $q->getResult();
    }

    private function dataProvider()
    {
        $tmpCreatedDate = new \DateTime();
        $tmpCreatedDate->modify('first day of this month');
        $tmpCreatedDate->setTime(0, 0, 0);

        $datesFromTo = [];
        for ($i = 7; $i <= 12; $i++) {
            $dateFrom = new \DateTime();
            $dateFrom->setDate(2019, $i, 1);
            $dateTo = clone $dateFrom;
            $dateTo->modify('last day of this month');
            $dateOfPayment = new \DateTime();
            $dateOfPayment->setDate(2019, $i, 14);
            $datesFromTo[] = [
                'from' => $dateFrom,
                'to' => $dateTo,
                'payment' => $dateOfPayment,
            ];
        }

        return [
            'createdDate' => $tmpCreatedDate,
            'dates' => $datesFromTo,
            'billingPeriods' => [201907, 201908, 201909, 201910, 201911, 201912],
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $clients = $this->getClients();


        if (!$clients) {
            dump('Generowanie faktur wyłączone - nie znaleziono oznaczonych klientów.');
            return;
        }

        // generate invoices
        $kernelRootDir = $this->container->get('kernel')->getRootDir();
        $clientCounter = 1;

        $dataProvider = $this->dataProvider();

        /** @var Client $client */
        foreach ($clients as $client) {
            // CLIENT IS ONLY ONE
            $invoices = $this->invoiceModel->getInvoicesByClient($client, 'WecodersEnergyBundle:InvoiceProforma');
            dump('client: ' . $client->getName() . ' ' . $client->getSurname());
            dump('count invoices:');
            dump(count($invoices));

            $payments = $this->paymentModel->getPaymentsByNumber($client->getAccountNumberIdentifier()->getNumber());
            $balance = new Balance();
            $balance->setInitialBalance($client->getInitialBalance());
            $balance->setToPay($this->invoiceModel->calculateSummaryFromInvoicesProforma($invoices));
            $balance->setPaid($this->paymentModel->calculateSummaryFromPayments($payments));
            $currentBalance = $balance->getBalance();

            // GETS CURRENT INVOICES
            $actualInvoices = $this->em->getRepository('WecodersEnergyBundle:InvoiceProforma')->findBy([
                'client' => $client
                ], ['billingPeriod' => 'ASC']);

            // CLONES CURRENT INVOICES
            $clonedInvoices = [];
            foreach ($actualInvoices as $invoice) {
                $clonedInvoices[] = clone $invoice;
            }

            $this->numberModel->init($kernelRootDir, null, $this->em, new \DateTime());
            // SAVES CLONED INVOICES WITH CHANGED DATA
            /** @var \Wecoders\EnergyBundle\Entity\InvoiceInterface $invoice */
            $index = 1;
            $loopIndex = 0;
            $balanceAdded = 0;
            foreach ($clonedInvoices as $invoice) {
                // save only 6 files, data providers are only set to 6 loops
                if ($loopIndex > 5) {
                    break;
                }

                // sets balance before
                $summaryGrossValue = $invoice->getSummaryGrossValue();
                if ($currentBalance > 0 && $balanceAdded != $currentBalance) {
                    // BALANCE IS MORE THAN 0 - ADDS ALL TO FIRST INVOICE
                    $invoice->setBalanceBeforeInvoice($currentBalance);
                    $balanceAdded = $currentBalance;
                } elseif ($currentBalance < 0 && $balanceAdded != $currentBalance) {
                    // BALANCE IS NEGATIVE - SPLIT
                    $absCurrentValue = abs($currentBalance) - $balanceAdded;

                    if ($absCurrentValue - $summaryGrossValue > 0) {
                        // CAN EXTRACT ALL VALUE WITH REST
                        $invoice->setBalanceBeforeInvoice(-$absCurrentValue);
                        $balanceAdded += $summaryGrossValue;
                    } elseif ($absCurrentValue - $summaryGrossValue < 0) {
                        // CANNOT EXTRACT ALL VALUE
                        $invoice->setBalanceBeforeInvoice(-$absCurrentValue);
                        $balanceAdded += $absCurrentValue;
                    } else {
                        // CAN EXTRACT ALL VALUE WITHOUT REST
                        $invoice->setBalanceBeforeInvoice(-$absCurrentValue);
                        $balanceAdded += $summaryGrossValue;
                    }
                }



                $currentClient = $this->em->getRepository('GCRMCRMBundle:Client')->find($client->getId());
                $invoice->setClient($currentClient);

                $invoice->setBillingPeriod($dataProvider['billingPeriods'][$loopIndex]);
                $invoice->setBillingPeriodFrom($dataProvider['dates'][$loopIndex]['from']);
                $invoice->setBillingPeriodTo($dataProvider['dates'][$loopIndex]['to']);
                $invoice->setDateOfPayment($dataProvider['dates'][$loopIndex]['payment']);
                $invoice->setCreatedDate($dataProvider['createdDate']);


                $tokensWithReplacement = [];
                $generatedNumber = $this->numberModel->generate($tokensWithReplacement, 'WecodersEnergyBundle:InvoiceProforma', 'number', 'proforma');
                if (!$generatedNumber) {
                    die('Nie można wygenerować numeru faktury. Sprawdź czy generowanie numeru faktury zostało prawidłowo ustawione.');
                }
                $invoice->setNumber($generatedNumber);


                // GENERATE INVOICE FILES
                /** @var InvoiceTemplate $invoiceTemplate */
                $invoiceTemplate = $invoice->getInvoiceTemplate();
                if (!$invoiceTemplate || !$invoiceTemplate->getFilePath() || !file_exists($this->invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath()))) {
                    die('Szablon faktury nie istnieje');
                }
                $templateAbsolutePath = $this->invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath());

                dump('Generate invoice file.');

                $directoryRelative = $this->easyAdminModel->getEntityDirectoryRelativeByEntityName('InvoiceProformaEnergy');
                $invoicePath = $this->invoiceModel->fullInvoicePath($kernelRootDir, $invoice, $directoryRelative);

                $this->energyBundleInvoiceModel->generateInvoiceProforma($invoice, $invoicePath, $templateAbsolutePath);
                dump('File generated.');


                // SAVE INVOICE DATA
                $this->em->detach($invoice);
                $this->em->persist($invoice);
                $this->em->flush();

                dump($index);
                $index++;
                $loopIndex++;
            }

            $client->setIsInvoiceGenerated(true);
            $this->em->merge($client);
            $this->em->flush();
            $this->em->clear();

            dump('done: ' . $client->getName() . ' ' . $client->getSurname());


            // MAKE THIS SCRIPT AGAIN
//            dump('Make this script again.');
//            $path = $this->container->get('kernel')->getRootDir() . '/../bin/console';
//            shell_exec('php ' . $path . ' wecodersenergybundle:generate-invoices-for-marked-clients');

            dump('count: ' . $clientCounter);
            $clientCounter++;
        }

        dump('Success');
    }
}