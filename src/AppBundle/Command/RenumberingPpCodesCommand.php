<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergyAndPpCode;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class RenumberingPpCodesCommand extends Command
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
        $this->setName('appbundle:renumbering-pp-codes')
            ->addArgument('filename', InputArgument::REQUIRED, 'Xlsx with 2 columns - from pp - to pp. Data starts from second row.')
            ->addArgument('entity', InputArgument::REQUIRED, 'Full linked with pp code entity. GCRMCRMBundle:ContractEnergyAndPpCode or GCRMCRMBundle:ContractGasAndPpCode')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $fileActual = $this->container->get('kernel')->getRootDir() . '/../var/data/pp-change/' . $input->getArgument('filename');
        $rows = $this->spreadsheetReader->fetchRows('Xlsx', $fileActual, 2, 'B');

        $index = 1;
        foreach ($rows as $row) {
            $records = $this->em->getRepository($input->getArgument('entity'))->findBy(['ppCode' => $row[0]]);
            if (!$records) {
                dump('not found: ' . $row[0]);
                continue;
            }

            /** @var ContractEnergyAndPpCode $record */
            foreach ($records as $record) {
                $record->setPpCode($row[1]);

                $this->em->persist($record);
                $this->em->flush($record);
            }

            dump($index);
            $index++;
        }

        dump('Success');
    }

}