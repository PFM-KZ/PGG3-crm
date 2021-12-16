<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\AccountNumberMaker;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateClientBankAccountNumberFromAccountNumberIdentifierCommand extends Command
{
    private $em;

    private $clientModel;

    private $accountNumberMaker;

    private $companyModel;

    public function __construct(
        EntityManager $em,
        ClientModel $clientModel,
        AccountNumberMaker $accountNumberMaker,
        CompanyModel $companyModel
    )
    {
        $this->em = $em;
        $this->clientModel = $clientModel;
        $this->accountNumberMaker = $accountNumberMaker;
        $this->companyModel = $companyModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('gcrmcrmbundle:update-client-bank-account-number-from-account-number-identifier')
            ->addArgument('accountNumberIdentifier', InputArgument::REQUIRED, 'Account number identifier')
            ->addArgument('staticTenNumbers', InputArgument::REQUIRED, '10 numbers')
            ->addArgument('staticFourNumbers', InputArgument::REQUIRED, '4 numbers')
            ->setDescription('Update client bank account number.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $accountNumberIdentifier = $input->getArgument('accountNumberIdentifier');

        /** @var Client $client */
        $client = $this->clientModel->getClientByBadgeId($accountNumberIdentifier);
        if (!$client) {
            dump('Client not found');
            die;
        }

        $bankAccountNumber = $this->accountNumberMaker->generateBankAccountNumber($accountNumberIdentifier, $input->getArgument('staticTenNumbers'), $input->getArgument('staticFourNumbers'));

        $client->setPreviousBankAccountNumber($client->getBankAccountNumber());
        $client->setBankAccountNumber($bankAccountNumber);
        $this->em->persist($client);
        $this->em->flush($client);

        dump('Success');
    }
}