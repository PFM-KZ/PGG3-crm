<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Service\ContractModel;

class InitialUpdateClientIsCompanyCommand extends Command
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
        $this->setName('appbundle:initial-update-client-is-company-field')
            ->setDescription('Initial update payer etc.');
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
            if ($client->getNip() && $client->getCompanyName()) {
                $client->setIsCompany(true);
                dump('is company: ' . $client->getAccountNumberIdentifier()->getNumber());
            } else {
                dump('not company: ' . $client->getAccountNumberIdentifier()->getNumber());
                $client->setIsCompany(false);
            }

            $this->em->persist($client);
            $this->em->flush($client);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}