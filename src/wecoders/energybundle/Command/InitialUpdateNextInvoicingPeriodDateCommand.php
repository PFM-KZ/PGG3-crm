<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractEnergy;
use GCRM\CRMBundle\Entity\ClientAndContractGas;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Entity\ContractInterface;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;
use Wecoders\InvoiceBundle\Service\Helper;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;
use Wecoders\InvoiceBundle\Service\NumberModel;

class InitialUpdateNextInvoicingPeriodDateCommand extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;
    private $clientModel;

    public function __construct(ContainerInterface $container, EntityManager $em, ClientModel $clientModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->clientModel = $clientModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:initial-update-next-invoicing-period-date')
            ->setDescription('Sets next invoicing period date - this can be made only once (from last invoice + 1 day to billingPeriodTo).');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $invoices = $this->em->getRepository('WecodersEnergyBundle:InvoiceProforma')->findAll();

        $clients = [];
        /** @var InvoiceProforma $invoice */
        foreach ($invoices as $invoice) {
            /** @var Client $client */
            $client = $invoice->getClient();
            if (!$client) {
                dump('Klient nie istnieje - faktura proforma nr: ' . $invoice->getId());
                continue;
            }

            if (!array_key_exists($client->getId(), $clients)) {
                $clients[$client->getId()]['client'] = $client;
                $clients[$client->getId()]['invoices'] = [];
                $clients[$client->getId()]['maxBillingPeriodTo'] = $invoice->getBillingPeriodTo();
            }
            $clients[$client->getId()]['invoices'][] = $invoice;
            $clients[$client->getId()]['maxBillingPeriodTo'] = $invoice->getBillingPeriodTo() > $clients[$client->getId()]['maxBillingPeriodTo'] ? $invoice->getBillingPeriodTo() : $clients[$client->getId()]['maxBillingPeriodTo'];
        }

        $index = 1;
        foreach ($clients as $clientData) {
            $client = $clientData['client'];
            $contract = null;
            $data = $client->getClientAndEnergyContracts();
            if ($data) {
                if (count($data) && count($data) > 1) {
                    die('2 umowy w karcie klienta');
                } elseif (count($data)) {
                    /** @var ContractEnergy $contract */
                    $contract = $data[0]->getContract();
                    if (!$contract) {
                        die('umowa not found');
                    }

                    if (!$contract->getIsResignation() && !$contract->getIsBrokenContract()) {
                        $nextInvoiceBillingPeriod = $clientData['maxBillingPeriodTo'];
                        $nextInvoiceBillingPeriod->modify('+1 day');

                        if (!$contract->getNextInvoicingPeriod()) {
                            $contract->setNextInvoicingPeriod($nextInvoiceBillingPeriod);
                            $em->persist($contract);
                            $em->flush();
                            dump($index);
                            $index++;
                            $em->clear();
                        }
                    }
                }
            }

            $data = $client->getClientAndGasContracts();
            if ($data) {
                if (count($data) && count($data) > 1) {
                    die('2 umowy w karcie klienta');
                } elseif (count($data)) {
                    /** @var ContractGas $contract */
                    $contract = $data[0]->getContract();
                    if (!$contract) {
                        die('umowa not found');
                    }

                    if (!$contract->getIsResignation() && !$contract->getIsBrokenContract()) {
                        $nextInvoiceBillingPeriod = $clientData['maxBillingPeriodTo'];
                        $nextInvoiceBillingPeriod->modify('+1 day');

                        if (!$contract->getNextInvoicingPeriod()) {
                            $contract->setNextInvoicingPeriod($nextInvoiceBillingPeriod);
                            $em->persist($contract);
                            $em->flush();
                            dump($index);
                            $index++;
                            $em->clear();
                        }
                    }
                }
            }
        }

        dump('Success');
    }

}