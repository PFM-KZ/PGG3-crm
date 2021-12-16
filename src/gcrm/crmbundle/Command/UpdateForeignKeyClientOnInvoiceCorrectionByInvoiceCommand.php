<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\InvoiceInterface;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\InvoiceModel;
use GCRM\CRMBundle\Service\ModulesModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateForeignKeyClientOnInvoiceCorrectionByInvoiceCommand extends Command
{
    /* @var EntityManager */
    private $em;

    /** @var InvoiceModel */
    private $invoiceModel;

    private $container;

    public function __construct(EntityManager $em, InvoiceModel $invoiceModel, ContainerInterface $container)
    {
        $this->em = $em;
        $this->invoiceModel = $invoiceModel;
        $this->container = $container;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('gcrmcrmbundle:update-foreign-key-client-on-invoice-correction-by-invoice')
            ->setDescription('Update clients on invoices corrections invoice.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $invoiceCorrections = $this->em->getRepository('GCRMCRMBundle:InvoiceCorrection')->findAll();

        // make an update
        /** @var Client $client */
        $index = 1;
        foreach ($invoiceCorrections as $invoiceCorrection) {
            // if have invoice set
            $invoice = $invoiceCorrection->getInvoice();
            if (!$invoice) {
                continue;
            }

            $client = $invoice->getClient();
            if (!$client) {
                dump('CLIENT NOT FOUND FOR INVOICE. UPDATE INVOICES FIRST AND TRY AGAIN');
                die;
            }

            // if already have client set
            if ($invoiceCorrection->getClient()) {
                continue;
            }

            /** @var InvoiceInterface $invoice */
            $invoiceCorrection->setClient($client);

            $this->em->persist($invoiceCorrection);
            $this->em->flush();

            dump($index);
            $index++;
        }
        dump('Success');
    }
}