<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;

class CheckForDuplicatesInSettlementsIncludedDocumentsCommand extends Command
{
    private $em;
    private $contractModel;
    private $initializer;
    private $clientModel;

    public function __construct(
        EntityManager $em,
        ContractModel $contractModel,
        Initializer $initializer,
        ClientModel $clientModel
    )
    {
        $this->em = $em;
        $this->contractModel = $contractModel;
        $this->initializer = $initializer;
        $this->clientModel = $clientModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:check-for-duplicates-in-settlements-included-documents')
            ->setDescription('');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $clients = $this->clientModel->getRecords();
        if (!$clients) {
            dump('Clients not found.');
            die;
        }

        /** @var Client $client */
        $index = 1;
        $duplicates = [];
        foreach ($clients as &$client) {
            $initialized = $this->initializer->init($client);
            $includedDocuments = $initialized->getInvoicesIncludedInSettlements();
            if (!count($includedDocuments)) {
                dump($index);
                $index++;
                continue;
            }

            $numbers = [];
            /** @var SettlementIncludedDocument $document */
            foreach ($includedDocuments as &$document) {
                $number = $document->getDocumentNumber();
                if (!$number) {
                    dump('ERROR - document number is empty');
                    die;
                }

                $numbers[] = $number;
            }
            $includedDocuments = null;

            $values = array_unique(array_diff_assoc($numbers, array_unique($numbers)));
            if (count($values)) {
                dump('Duplicates: ' . $client->getId());
                $duplicates[] = $client->getId();
            }
            $client = null;
            $initialized = null;

            dump($index);
            $index++;
        }

        dump($duplicates);
        dump(count($duplicates));

        dump('Success');
    }

}