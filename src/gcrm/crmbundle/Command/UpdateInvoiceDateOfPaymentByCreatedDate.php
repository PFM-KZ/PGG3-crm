<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Invoice;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\InvoiceModel;
use GCRM\CRMBundle\Service\ModulesModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateInvoiceDateOfPaymentByCreatedDate extends Command
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
        $this->setName('gcrmcrmbundle:update-invoice-date-of-payment-by-created-date')
            ->addArgument('days', InputArgument::REQUIRED, 'days (int) from created date')
            ->addArgument('type', InputArgument::REQUIRED, 'type of invoice: invoice, correction, proforma')
            ->setDescription('Update invoices date of payment by created date.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        $days = $input->getArgument('days');

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $index = 1;
        if ($type == 'invoice') {
            $invoices = $this->em->getRepository('GCRMCRMBundle:Invoice')->findAll();
            if (!$invoices) {
                dump('No invoices');
                return;
            }

            /** @var Invoice $invoice */
            foreach ($invoices as $invoice) {
                if ($invoice->getDateOfPayment()) {
                    continue;
                }

                $date = clone $invoice->getCreatedDate();
                $date->modify('+' . $days . ' days');
                $date->setTime(0, 0, 0);
                $invoice->setDateOfPayment($date);

                $this->em->persist($invoice);
                $this->em->flush();

                dump($index);
                $index++;
            }

            dump('Success');
        }
    }
}