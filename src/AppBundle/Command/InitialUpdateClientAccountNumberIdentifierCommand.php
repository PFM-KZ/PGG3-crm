<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Service\AccountNumberIdentifierModel;
use GCRM\CRMBundle\Entity\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Service\ContractModel;

class InitialUpdateClientAccountNumberIdentifierCommand extends Command
{
    private $em;
    private $contractModel;
    private $accountNumberIdentifierModel;

    public function __construct(EntityManagerInterface $em, ContractModel $contractModel, AccountNumberIdentifierModel $accountNumberIdentifierModel)
    {
        $this->em = $em;
        $this->contractModel = $contractModel;
        $this->accountNumberIdentifierModel = $accountNumberIdentifierModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:initial-update-account-number-identifier')
            ->setDescription('Initial update account number identifier');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('GCRMCRMBundle:Client', 'a')
            ->getQuery()
        ;

        $clients = $q->getResult();

        /** @var Client $client */
        $index = 1;
        foreach ($clients as $client) {
            if ($client->getAccountNumberIdentifier()) {
                continue;
            }

            if ($this->accountNumberIdentifierModel->isUsed($client->getBadgeId())) {
                dump('Used: ' . $client->getBadgeId());
                continue;
            }

            $accountNumberIdentifier = $this->accountNumberIdentifierModel->add($client->getBadgeId());
            $client->setAccountNumberIdentifier($accountNumberIdentifier);

            $this->em->persist($client);
            $this->em->flush($client);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}