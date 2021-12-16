<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Service;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection;
use Wecoders\EnergyBundle\Service\BillingDocument\Document\InvoiceEstimatedSettlementCorrection;
use Wecoders\EnergyBundle\Service\ContractAccessor;

class InitialUpdateInvoiceProformaOnSettlementsCorrectionsCommand extends Command
{
    /* @var EntityManager */
    private $em;

    private $initializer;

    private $clientModel;

    private $contractAccessor;

    private $contractModel;

    public function __construct(
        EntityManager $em,
        Initializer $initializer,
        ClientModel $clientModel,
        ContractAccessor $contractAccessor,
        ContractModel $contractModel
    )
    {
        $this->em = $em;
        $this->initializer = $initializer;
        $this->clientModel = $clientModel;
        $this->contractAccessor = $contractAccessor;
        $this->contractModel = $contractModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('gcrmcrmbundle:initial-update-invoice-proforma-on-settlements-corrections')
            ->setDescription('Update tables.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $clients = $this->clientModel->getRecords();

        /** @var Client $client */
        $clientCount = 1;
        $errors = [];

        foreach ($clients as $client) {
            $initializer = $this->initializer->init($client)->generate();
            $bag = $initializer->getDocumentsBag();

            $index = 1;
            for ($i = 0; $i < count($bag); $i++) {
                $document = $bag[$i];

                // catch invoice settlement and invoice estimated settlement documents / omit other types
                if (!($document instanceof InvoiceSettlementCorrection) && !($document instanceof InvoiceEstimatedSettlementCorrection)) {
                    continue;
                }

                dump($client->getSurname());
                dump($client->getAccountNumberIdentifier()->getNumber());

                // here are only settlement types documents
                $includedDocuments = $document->getInvoice()->getIncludedDocuments();
                $document->setIncludedDocuments($includedDocuments);

                $this->em->persist($document);
                $this->em->flush($document);

                $index++;
            }

            dump($clientCount);
            $clientCount++;


        }

        dump($errors);



        // append proformas to settlements

        dump('Success');
    }

    public function countSettlementTypesDocuments(&$bag)
    {
        $count = 0;
        for ($i = 0; $i < count($bag); $i++) {
            $document = $bag[$i];
            if ($document instanceof InvoiceSettlement || $document instanceof InvoiceEstimatedSettlement) {
                $count++;
            }
        }

        return $count;
    }
}