<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractEnergy;
use GCRM\CRMBundle\Entity\ClientAndContractGas;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Entity\ContractInterface;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;
use Wecoders\InvoiceBundle\Service\Helper;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;
use Wecoders\InvoiceBundle\Service\NumberModel;

class MarkUsersToGenerateInvoicesCommand extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;
    private $clientModel;

    public function __construct(ContainerInterface $container, EntityManager $em, ClientModel $clientModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->clientModel = $clientModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:mark-users-to-generate-invoices')
            ->setDescription('Mark users to generate invoices.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $clients = $this->getClientsReadyToGenerateInvoice($em);

        // mark clients
        /** @var Client $client */
        foreach ($clients as $client) {
            $client->setIsMarkedToGenerateInvoice(true);
            $client->setIsInvoiceGenerated(false);
            $em->persist($client);
            $em->flush();
        }

        dump('Success');
    }

    private function getClientsReadyToGenerateInvoice(EntityManager $em)
    {
        /** @var StatusDepartment $statusDepartment */
        $statusDepartment = $this->em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy(['code' => 'finances']);

        if (!$statusDepartment) {
            die('Status departamentu finansowego nie istnieje. Skontaktuj się z administratorem.');
        }

        $qb = $em->createQueryBuilder();
        $q = $qb->select(['entity'])
            ->from('GCRMCRMBundle:Client', 'entity')
        ;

        $clients = $q->getQuery()->getResult();
        $filteredClients = [];

        /** @var Client $client */
        foreach ($clients as $client) {
            $hasAnyValidContract = false;

            // Gas
            $clientAndContracts = $client->getClientAndGasContracts();
            $clientAndContractsToCalculate = $this->filterClientAndContracts($clientAndContracts, $statusDepartment);
            $client->setClientAndGasContracts($clientAndContractsToCalculate);
            if (!$hasAnyValidContract) { $hasAnyValidContract = count($clientAndContractsToCalculate) ? true : false; }

            // Energy
            $clientAndContracts = $client->getClientAndEnergyContracts();
            $clientAndContractsToCalculate = $this->filterClientAndContracts($clientAndContracts, $statusDepartment);
            $client->setClientAndEnergyContracts($clientAndContractsToCalculate);
            if (!$hasAnyValidContract) { $hasAnyValidContract = count($clientAndContractsToCalculate) ? true : false; }

            if ($hasAnyValidContract) {
                $filteredClients[] = $client;
            }
        }
        return $filteredClients;
    }

    private function filterClientAndContracts($clientAndContracts, StatusDepartment $statusDepartment)
    {
        $clientAndContractsToCalculate = [];
        foreach ($clientAndContracts as $clientAndContract) {
            /** @var ContractInterface $contract */
            $contract = $clientAndContract->getContract();
            if (!$contract) {
                continue;
            }
            $contractStatusDepartment = $contract->getStatusDepartment();
            // filter contracts
            if (
                ($contractStatusDepartment && $contractStatusDepartment->getId() == $statusDepartment->getId()) &&
                ($contract->getIsResignation() != 1 && $contract->getIsBrokenContract() != 1) // &&
//                        ($contract->getCreatedAt() >= $dateStartFrom)
            ) {
                $clientAndContractsToCalculate[] = $clientAndContract;
            }
        }

        return $clientAndContractsToCalculate;
    }


}