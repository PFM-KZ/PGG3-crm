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

class InitialUpdatePriceListsSingleToMultipleCommand extends Command
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
        $this->setName('appbundle:initial-update-price-lists-single-to-multiple')
            ->setDescription('Initial update price lists single to multiple - rewrite (ENERGY - GAS).');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $contracts = $this->contractModel->getContracts();

        /** @var ContractEnergyBase $contract */
        $index = 1;
        foreach ($contracts as $contract) {
            if (!$contract->getPriceList()) {
                continue;
            }

            if (count($contract->getContractAndPriceLists()->toArray())) {
                continue;
            }

            $priceList = $contract->getPriceList();

            if ($contract->getType() == 'GAS') {
                $contractAndPriceList = new ContractGasAndPriceList();
                $contractAndPriceList->setPriceList($priceList);
                $contractAndPriceList->setContract($contract);
            } elseif ($contract->getType() == 'ENERGY') {
                $contractAndPriceList = new ContractEnergyAndPriceList();
                $contractAndPriceList->setPriceList($priceList);
                $contractAndPriceList->setContract($contract);
            } else {
                dump('BAD CONTRACT TYPE');
                die;
            }

            $contract->addContractAndPriceList($contractAndPriceList);
            $this->em->persist($contract);
            $this->em->flush($contract);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}