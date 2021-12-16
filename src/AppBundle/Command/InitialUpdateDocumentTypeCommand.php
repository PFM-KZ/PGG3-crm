<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Service\ContractModel;

class InitialUpdateDocumentTypeCommand extends Command
{
    private $em;
    private $contractModel;

    public function __construct(EntityManager $em, ContractModel $contractModel)
    {
        $this->em = $em;
        $this->contractModel = $contractModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:initial-update-document-type')
            ->addArgument('entityName', InputArgument::REQUIRED, 'Give full entity name')
            ->setDescription('Initial update documents type (ENERGY - GAS).');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $invoices = $this->em->getRepository($input->getArgument('entityName'))->findAll();
        $errors = [];
        /** @var InvoiceInterface $invoice */
        $index = 1;
        foreach ($invoices as $invoice) {
            // ommit records that are already setup
            if ($invoice->getType()) {
                continue;
            }

            $contractNumber = $invoice->getContractNumber();
            if (!$contractNumber) {
                $errors[] = 'Not found contract number on invoice: ' . $invoice->getNumber();
                continue;
            }

            if (!$invoice->getClient()) {
                $errors[] = 'Contract does not have client: ' . $contractNumber;
                continue;
            }

            $contractObject = $this->contractModel->getContractByNumber($invoice->getClient(), $contractNumber);
            if (!$contractObject) {
                $errors[] = 'Contract not found for client: ' . $invoice->getClient()->getBadgeId() . ' and contract: ' . $contractNumber;
                continue;
            }

            $invoice->setType($contractObject->getType());
            $this->em->persist($invoice);
            $this->em->flush($invoice);

            $index++;
            dump($index);
        }
        dump($errors);

        dump('Success');
    }

}