<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergyAndPpCode;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\ContractGasAndPpCode;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class InsertNewPpCodesCommand extends Command
{
    private $em;
    private $contractModel;
    private $container;
    private $spreadsheetReader;
    private $clientModel;
    private $contractAccessor;

    public function __construct(
        EntityManager $em,
        ContractModel $contractModel,
        ContainerInterface $container,
        SpreadsheetReader $spreadsheetReader,
        ClientModel $clientModel,
        ContractAccessor $contractAccessor
    )
    {
        $this->em = $em;
        $this->contractModel = $contractModel;
        $this->container = $container;
        $this->spreadsheetReader = $spreadsheetReader;
        $this->clientModel = $clientModel;
        $this->contractAccessor = $contractAccessor;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:insert-new-pp-codes')
            ->addArgument('filename', InputArgument::REQUIRED, 'Xlsx with 3 columns - id client - from pp - to pp. Data starts from second row.')
            ->addArgument('fromYear', InputArgument::REQUIRED, 'From year')
            ->addArgument('fromMonth', InputArgument::REQUIRED, 'From month')
            ->addArgument('fromDay', InputArgument::REQUIRED, 'From day')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();

        $fileActual = $kernelRootDir . '/../var/data/pp-change/' . $input->getArgument('filename');
        $rows = $this->spreadsheetReader->fetchRows('Xlsx', $fileActual, 2, 'C');

        $fromDate = (new \DateTime())->setDate(
            $input->getArgument('fromYear'),
            $input->getArgument('fromMonth'),
            $input->getArgument('fromDay')
        )->setTime(0, 0);

        $index = 1;
        foreach ($rows as $row) {
            $client = $this->clientModel->getClientByBadgeId($row[0]);
            if (!$client) {
                dump('Brak klienta: ' . $row[0]);
                continue;
            }

            /** @var ContractEnergyBase $contract */
            $contract = $this->contractAccessor->accessContractBy('id', $client->getId(), 'client');
            if (!$contract) {
                dump('Brak umowy: ' . $row[0]);
                continue;
            }

            if ($contract->getType() == 'GAS') {
                $contractAndPpCode = new ContractGasAndPpCode();
            } elseif ($contract->getType() == 'ENERGY') {
                $contractAndPpCode = new ContractEnergyAndPpCode();
            } else {
                dump('ERROR');
                die;
            }

            $contractAndPpCode->setContract($contract);
            $contractAndPpCode->setPpCode($row[2]);
            $contractAndPpCode->setFromDate($fromDate);

            $contract->addContractAndPpCode($contractAndPpCode);
            $this->em->persist($contract);
            $this->em->flush($contract);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}