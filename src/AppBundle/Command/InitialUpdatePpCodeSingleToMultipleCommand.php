<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergyAndPpCode;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\ContractGasAndPpCode;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitialUpdatePpCodeSingleToMultipleCommand extends Command
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
        $this->setName('appbundle:initial-update-ppcode-single-to-multiple')
            ->setDescription('Initial update pp codes single to multiple - rewrite (ENERGY - GAS).');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $contracts = $this->contractModel->getContracts();

        /** @var ContractEnergyBase $contract */
        $index = 1;
        foreach ($contracts as $contract) {
            if (!$contract->getPpCode()) {
                continue;
            }

            if (count($contract->getContractAndPpCodes()->toArray())) {
                continue;
            }

            $ppCode = $contract->getPpCode();

            if ($contract->getType() == 'GAS') {
                $contractAndPpCode = new ContractGasAndPpCode();
                $contractAndPpCode->setPpCode($ppCode);
                $contractAndPpCode->setContract($contract);
            } elseif ($contract->getType() == 'ENERGY') {
                $contractAndPpCode = new ContractEnergyAndPpCode();
                $contractAndPpCode->setPpCode($ppCode);
                $contractAndPpCode->setContract($contract);
            } else {
                dump('BAD CONTRACT TYPE');
                die;
            }

            $contract->addContractAndPpCode($contractAndPpCode);
            $this->em->persist($contract);
            $this->em->flush($contract);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}