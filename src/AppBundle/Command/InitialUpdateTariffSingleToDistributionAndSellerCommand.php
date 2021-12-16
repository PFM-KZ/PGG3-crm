<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractEnergyAndDistributionTariff;
use GCRM\CRMBundle\Entity\ContractEnergyAndSellerTariff;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Entity\ContractGasAndDistributionTariff;
use GCRM\CRMBundle\Entity\ContractGasAndSellerTariff;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitialUpdateTariffSingleToDistributionAndSellerCommand extends Command
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
        $this->setName('appbundle:initial-update-tariff-single-to-distribution-and-seller')
            ->setDescription('Initial update tariffs single to multiple - rewrite (ENERGY - GAS).');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $contracts = $this->contractModel->getContracts();

        /** @var ContractEnergyBase $contract */
        $index = 1;
        foreach ($contracts as $contract) {
            if (!$contract->getTariff()) {
                continue;
            }

            if (
                count($contract->getContractAndDistributionTariffs()->toArray()) ||
                count($contract->getContractAndSellerTariffs()->toArray())
            ) {
                continue;
            }

            $tariff = $contract->getTariff();

            if ($contract->getType() == 'GAS') {
                $contractAndTariff = new ContractGasAndDistributionTariff();
                $contractAndTariff->setTariff($tariff);
                $contractAndTariff->setContract($contract);

                /** @var ContractGas $contract */
                $contract->addContractAndDistributionTariff($contractAndTariff);

                $contractAndTariff = new ContractGasAndSellerTariff();
                $contractAndTariff->setTariff($tariff);
                $contractAndTariff->setContract($contract);

                $contract->addContractAndSellerTariff($contractAndTariff);
            } elseif ($contract->getType() == 'ENERGY') {
                $contractAndTariff = new ContractEnergyAndDistributionTariff();
                $contractAndTariff->setTariff($tariff);
                $contractAndTariff->setContract($contract);

                /** @var ContractEnergy $contract */
                $contract->addContractAndDistributionTariff($contractAndTariff);

                $contractAndTariff = new ContractEnergyAndSellerTariff();
                $contractAndTariff->setTariff($tariff);
                $contractAndTariff->setContract($contract);

                $contract->addContractAndSellerTariff($contractAndTariff);
            } else {
                dump('BAD CONTRACT TYPE');
                die;
            }

            $this->em->persist($contract);
            $this->em->flush($contract);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}