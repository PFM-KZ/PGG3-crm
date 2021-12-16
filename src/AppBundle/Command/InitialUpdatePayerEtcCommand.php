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

class InitialUpdatePayerEtcCommand extends Command
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
        $this->setName('appbundle:initial-update-payer-etc')
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
            $client->setPayerZipCode($client->getToPayerZipCode());
            $client->setPayerApartmentNr($client->getToPayerApartmentNr());
            $client->setPayerCity($client->getToPayerCity());
            $client->setPayerCompanyName($client->getToPayerCompanyName());
            $client->setPayerHouseNr($client->getToPayerHouseNr());
            $client->setPayerNip($client->getToPayerNip());
            $client->setPayerStreet($client->getToPayerStreet());

            $client->setRecipientZipCode($client->getToRecipientZipCode());
            $client->setRecipientApartmentNr($client->getToRecipientApartmentNr());
            $client->setRecipientCity($client->getToRecipientCity());
            $client->setRecipientCompanyName($client->getToRecipientCompanyName());
            $client->setRecipientHouseNr($client->getToRecipientHouseNr());
            $client->setRecipientNip($client->getToRecipientNip());
            $client->setRecipientStreet($client->getToRecipientStreet());

            $client->setIsPayerSameAsBuyer(false);
            $client->setIsPayerSameAsRecipient(false);
            $client->setIsRecipientSameAsBuyer(false);

            $this->em->persist($client);
            $this->em->flush($client);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}