<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateContractsActualStatusCommand extends Command
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
        $this->setName('appbundle:update-contracts-actual-status')
            ->addArgument('entityName', InputArgument::REQUIRED, 'Give full entity name of contract')
            ->setDescription('');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $contracts = $this->em->getRepository($input->getArgument('entityName'))->findAll();

        $index = 1;
        foreach ($contracts as $contract) {
            if (!method_exists($contract, 'setActualStatus')) {
                continue;
            }

            $actualStatus = $this->contractModel->manageActualStatus($contract);
            if (!$actualStatus) {
                continue;
            }

            $contract->setActualStatus($actualStatus);
            $this->em->persist($contract);
            $this->em->flush($contract);

            $index++;
            dump($index);
        }

        dump('Success');
    }

}