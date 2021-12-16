<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\InvoiceModel;
use GCRM\CRMBundle\Service\ModulesModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateAllClientsInvoicePaidStateCommand extends Command
{
    /* @var EntityManager */
    private $em;

    /** @var InvoiceModel */
    private $invoiceModel;

    private $container;

    private $initializer;

    public function __construct(EntityManager $em, InvoiceModel $invoiceModel, ContainerInterface $container, Initializer $initializer)
    {
        $this->em = $em;
        $this->invoiceModel = $invoiceModel;
        $this->container = $container;
        $this->initializer = $initializer;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('gcrmcrmbundle:update-clients-proforma-paid-state')
            ->addArgument('clientId', InputArgument::OPTIONAL, 'clientId - to calculate single client')
            ->setDescription('Update clients documents paid state.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientId = $input->getArgument('clientId');
        if ($clientId) {
            $singleClient = $this->em->getRepository('GCRMCRMBundle:Client')->find($clientId);
            $clients = [];
            if ($singleClient) {
                $clients[] = $singleClient;
            }
        } else {
            $clients = $this->em->getRepository('GCRMCRMBundle:Client')->findAll();
        }

        // make an update
        /** @var Client $client */
        $index = 1;
        foreach ($clients as $client) {
            $billingDocumentsObject = $this->initializer->init($client)->generate();
            $billingDocumentsObject->updateDocumentsIsPaidState();
            dump($index);
            $index++;
        }

        dump('Success');
    }


}