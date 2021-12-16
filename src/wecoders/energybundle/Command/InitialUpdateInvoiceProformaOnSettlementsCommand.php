<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\Service;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;
use Wecoders\EnergyBundle\Service\ContractAccessor;

class InitialUpdateInvoiceProformaOnSettlementsCommand extends Command
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
        $this->setName('gcrmcrmbundle:initial-update-invoice-proforma-on-settlements')
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

            $settlementDocumentsCount = $this->countSettlementTypesDocuments($bag);

            $index = 1;
            for ($i = 0; $i < count($bag); $i++) {
                $document = $bag[$i];

                // catch invoice settlement and invoice estimated settlement documents / omit other types
                if (!($document instanceof InvoiceSettlement) && !($document instanceof InvoiceEstimatedSettlement)) {
                    continue;
                }

                dump($client->getSurname());
                dump($client->getAccountNumberIdentifier()->getNumber());

                // here are only settlement types documents

                // manage is first settlement
                if ($index == 1) {
                    $document->setIsFirst(true);
                    $this->em->persist($document);
                    $this->em->flush($document);
                }

                // manage is last settlement
                if ($index == $settlementDocumentsCount) {
                    /** @var ContractEnergyBase $contract */
                    $contract = $this->contractAccessor->accessContractBy('id', $client->getId(), 'client');
                    if (!$contract) {
                        $errors[] = $client->getAccountNumberIdentifier()->getNumber();
                        continue;
                    }
                    if ($this->contractModel->hasContractEndStatus($contract)) {
                        $document->setIsLast(true);
                        $this->em->persist($document);
                        $this->em->flush($document);
                    }
                }

                $includedDocuments = $this->initializer->getDocumentsContainedInStatement(
                    $document->getBillingPeriodFrom(),
                    $document->getBillingPeriodTo(),
                    $document->getIsFirst(),
                    $document->getIsLast()
                );

                $settlementIncludedDocuments = [];
                /** @var InvoiceInterface $includedDocument */
                foreach ($includedDocuments as $includedDocument) {
                    $settlementIncludedDocument = new SettlementIncludedDocument();
                    $settlementIncludedDocument = $settlementIncludedDocument->create(
                        $includedDocument->getNumber(),
                        $includedDocument->getSummaryNetValue(),
                        $includedDocument->getSummaryVatValue(),
                        $includedDocument->getSummaryGrossValue(),
                        $includedDocument->getExciseValue(),
                        $includedDocument->getBillingPeriodFrom(),
                        $includedDocument->getBillingPeriodTo()
                    );
                    $settlementIncludedDocuments[] = $settlementIncludedDocument;
                }
                $document->setIncludedDocuments($settlementIncludedDocuments);

                $this->em->persist($document);
                $this->em->flush($document);

                $index++;
            }


            $initializer = $this->initializer->init($client)->generate();
            $initializer->updateDocumentsIsPaidState();

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