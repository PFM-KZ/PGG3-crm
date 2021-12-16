<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\AccountNumberIdentifier;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Service\AccountNumberMaker;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateClientsBankAccountNumberFromAccountNumberIdentifierCommand extends Command
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
        $this->setName('gcrmcrmbundle:update-clients-bank-account-number-from-account-number-identifier')
            ->setDescription('Update all clients bank account number to new one.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('GCRMCRMBundle:Client', 'a')
            ->getQuery()
        ;

        $clients = $q->getResult();
        if (!$clients) {
            dump('Clients not found');
            die;
        }

        /** @var Company $company */
        $company = $this->companyModel->getCompanyReadyForGenerateBankAccountNumbers();
        if (!$company) {
            dump('No company ready for generate bank account numbers');
            die;
        }

        // validate before process
        /** @var Client $client */
        foreach ($clients as $client) {
            /** @var AccountNumberIdentifier $accountNumberIdentifier */
            $accountNumberIdentifier = $client->getAccountNumberIdentifier();
            if (!$accountNumberIdentifier) {
                dump('No account number identifier');
                die;
            }

            if (!$accountNumberIdentifier->getNumber()) {
                dump('No number found in account number identifier');
                die;
            }
        }

        $index = 1;
        /** @var Client $client */
        foreach ($clients as $client) {
            $accountNumberIdentifier = $client->getAccountNumberIdentifier();

            $bankAccountNumber = $this->accountNumberMaker->generateBankAccountNumber(
                $accountNumberIdentifier->getNumber(),
                $company->getBankGeneratorStaticPartCodeOne(),
                $company->getBankGeneratorStaticPartCodeTwo()
            );

            $client->setPreviousBankAccountNumber($client->getBankAccountNumber());
            $client->setBankAccountNumber($bankAccountNumber);
            $this->em->persist($client);
            $this->em->flush($client);

            dump($index);
            $index++;
        }

        dump('Success');
    }
}