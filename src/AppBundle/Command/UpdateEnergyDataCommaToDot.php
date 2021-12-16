<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractEnergyAndPriceList;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\ContractGasAndPriceList;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\ContractEnergyInterface;
use Wecoders\EnergyBundle\Entity\EnergyData;

class UpdateEnergyDataCommaToDot extends Command
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
        $this->setName('appbundle:update-energy-data-comma-to-dot')
            ->setDescription('Fixes records by updating comma to dot values')
            ->addArgument('option', InputArgument::REQUIRED, '"all" or specific code');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $option = $input->getArgument('option');
        if ($option == 'all') {
            $energyDatas = $this->em->getRepository('WecodersEnergyBundle:EnergyData')->findAll();
        } else {
            $energyDatas = $this->em->getRepository('WecodersEnergyBundle:EnergyData')->findBy(['code' => $option]);
        }

        if (!$energyDatas) {
            dump('Not found energy datas');
        }

        $index = 1;

        /** @var EnergyData $energyData */
        foreach ($energyDatas as $energyData) {
            $stateStart = $energyData->getStateStart();
            if (is_string($stateStart)) {
                $stateStart = str_replace(',', '.', $stateStart);
            }
            $energyData->setStateStart($stateStart);


            $stateEnd = $energyData->getStateEnd();
            if (is_string($stateEnd)) {
                $stateEnd = str_replace(',', '.', $stateEnd);
            }
            $energyData->setStateEnd($stateEnd);


            $consumptionKwh = $energyData->getConsumptionKwh();
            if (is_string($consumptionKwh)) {
                $consumptionKwh = str_replace(',', '.', $consumptionKwh);
            }
            $energyData->setConsumptionKwh($consumptionKwh);


            $consumptionLossKwh = $energyData->getConsumptionLossKwh();
            if (is_string($consumptionLossKwh)) {
                $consumptionLossKwh = str_replace(',', '.', $consumptionLossKwh);
            }
            $energyData->setConsumptionLossKwh($consumptionLossKwh);

            $this->em->persist($energyData);
            $this->em->flush($energyData);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}