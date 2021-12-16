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

class UpdateForeignKeyClientOnInvoiceByBadgeIdCommand extends Command
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
        $this->setName('gcrmcrmbundle:update-foreign-key-client-on-invoice-by-badge-id')
            ->setDescription('Update clients on invoices by badge id.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $clients = $this->em->getRepository('GCRMCRMBundle:Client')->findAll();

        // make an update
        /** @var Client $client */
        $index = 1;
        foreach ($clients as $client) {
            $invoices = $this->invoiceModel->getInvoicesByBadgeId($client->getAccountNumberIdentifier()->getNumber());
            if ($invoices) {
                /** @var InvoiceInterface $invoice */
                foreach ($invoices as $invoice) {
                    $invoice->setClient($client);

                    $this->em->persist($invoice);
                    $this->em->flush();
                }
            }
            dump($index);
            $index++;
        }
        dump('Success');
    }
}