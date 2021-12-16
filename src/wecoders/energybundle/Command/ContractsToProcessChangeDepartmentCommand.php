<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\ClientModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContractsToProcessChangeDepartmentCommand extends Command
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
        $this->setName('wecodersenergybundle:contracts-to-process-change-department-command')
            ->setDescription('Contracts to process change department command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $contracts = $this->getContractsThatCanBeChecked();

        $statusDepartmentProcess = $this->em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy(['code' => 'process']);
        if (!$statusDepartmentProcess) {
            dump('Control department process not found.');
            return;
        }

        $statusActionGo = $this->em->getRepository('GCRMCRMBundle:StatusContractAction')->findOneBy(['code' => 'GO']);
        if (!$statusActionGo) {
            dump('Status action go not found.');
            return;
        }

        $statusContractControlWithGoStatus = $this->em->getRepository('GCRMCRMBundle:StatusContractControl')->findOneBy(['statusContractAction' => $statusActionGo]);
        if (!$statusContractControlWithGoStatus) {
            dump('Control status with action GO not found.');
            return;
        }

        $actualDate = new \DateTime();
        $actualDate->setTime(0, 0, 0);

        // here are all contracts that can be checked
        /** @var ContractEnergyBase $contract */
        foreach ($contracts as $contract) {
            $signDate = $contract->getSignDate();
            $signDate->setTime(0, 0, 0);

            if ($signDate >= $actualDate) {
                // set control status -> GO (do procesu)
                $contract->setStatusContractControl($statusContractControlWithGoStatus);
                $contract->setStatusDepartment($statusDepartmentProcess);
                $this->em->persist($contract);
                $this->em->flush($contract);
            }
        }

        dump('Success');
    }

    private function getContractsThatCanBeChecked()
    {
        // status department control
        $statusDepartmentControl = $this->em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy(['code' => 'control']);
        if (!$statusDepartmentControl) {
            dump('Control department status not found.');
            return;
        }

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select('a')
            ->from('GCRMCRMBundle:ClientAndContractGas', 'a')
            ->leftJoin(
                'GCRMCRMBundle:ContractGas',
                'b',
                'WITH',
                'a.contract = b.id'
            )
            ->where('a.client IS NOT NULL')
            ->andWhere('a.contract IS NOT NULL')
            ->andWhere('b.signDate IS NOT NULL')
            ->andWhere('b.isResignation != 1')
            ->andWhere('b.isBrokenContract != 1')
            ->andWhere('b.statusDepartment = :statusDepartment')
            ->setParameters([
                'statusDepartment' => $statusDepartmentControl
            ])
            ->getQuery()
        ;
        $result = $q->getResult();

        $contracts = [];

        if ($result) {
            foreach ($result as $clientAndContract) {
                $contracts[] = $clientAndContract->getContract();
            }
        }

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select('a')
            ->from('GCRMCRMBundle:ClientAndContractEnergy', 'a')
            ->leftJoin(
                'GCRMCRMBundle:ContractEnergy',
                'b',
                'WITH',
                'a.contract = b.id'
            )
            ->where('a.client IS NOT NULL')
            ->andWhere('a.contract IS NOT NULL')
            ->andWhere('b.signDate IS NOT NULL')
            ->andWhere('b.isResignation != 1')
            ->andWhere('b.isBrokenContract != 1')
            ->andWhere('b.statusDepartment = :statusDepartment')
            ->setParameters([
                'statusDepartment' => $statusDepartmentControl
            ])
            ->getQuery()
        ;
        $result = $q->getResult();

        if ($result) {
            foreach ($result as $clientAndContract) {
                $contracts[] = $clientAndContract->getContract();
            }
        }

        return $contracts;
    }
}