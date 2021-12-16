<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Service\AlertModel;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\StatusDepartmentModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\ContractEnergyInterface;

class AlertRecordingsCommand extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $statusDepartmentModel;

    private $alertModel;

    public function __construct(ContainerInterface $container, EntityManager $em, StatusDepartmentModel $statusDepartmentModel, AlertModel $alertModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->statusDepartmentModel = $statusDepartmentModel;
        $this->alertModel = $alertModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:alert-recordings')
            ->setDescription('Make alerts if recordings not found in departments above verification (from new clients - date from 2019.08.01 to -2 days from current date)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fromDate = new \DateTime();
        $fromDate->setDate(2019, 8, 1);
        $fromDate->setTime(0, 0, 0);

        $toDate = new \DateTime();
        $toDate->modify('-2 days');
        $toDate->setTime(0, 0, 0);

        $recordsEnergy = $this->recordsWithoutRecordings('GCRMCRMBundle:ClientAndContractEnergy', 'GCRMCRMBundle:ContractEnergy', 'GCRMCRMBundle:RecordingEnergyAttachment', $fromDate, $toDate);
        $recordsGas = $this->recordsWithoutRecordings('GCRMCRMBundle:ClientAndContractGas', 'GCRMCRMBundle:ContractGas', 'GCRMCRMBundle:RecordingGasAttachment', $fromDate, $toDate);

        $records = array_merge($recordsEnergy, $recordsGas);
        if (!$records) {
            dump('No records');
            die;
        }

        // filter from choosen departments
        $statusDepartments = $this->statusDepartmentModel->getRecords();
        if (!$statusDepartments) {
            dump('No departments');
            die;
        }

        $findInDepartments = ['administration', 'control', 'process', 'finances'];
        $findInDepartmentIds = [];
        /** @var StatusDepartment $statusDepartment */
        foreach ($statusDepartments as $statusDepartment) {
            if (in_array($statusDepartment->getCode(), $findInDepartments)) {
                $findInDepartmentIds[] = $statusDepartment->getId();
            }
        }
        if (!count($findInDepartmentIds)) {
            dump('No departments above verification');
            die;
        }

        $filteredRecords = [];
        /** @var ContractEnergyInterface $record */
        foreach ($records as $record) {
            $haveCorrectDepartment = $record->getStatusDepartment() && in_array($record->getStatusDepartment()->getId(), $findInDepartmentIds);
            if ($haveCorrectDepartment) {
                $filteredRecords[] = $record;
            }
        }

        if (!count($filteredRecords)) {
            dump('No records after filter');
            die;
        }

        $title = 'Niektóre z umów (' . count($filteredRecords) . ') nie mają nagrań. ';
        $numbers = [];
        /** @var ContractEnergyInterface $record */
        foreach ($filteredRecords as $record) {
            $numbers[] = $record->getContractNumber();
        }
        $content = 'Numery umów (sprawdzanie od 2019-08-01 do -2 dni od aktualnej daty): ' . implode(',', $numbers);

        $this->alertModel->add(AlertModel::CODE_NOTICE, $title, $content);

        dump('Success');
    }

    private function recordsWithoutRecordings($clientAndContractEntity, $contractEntity, $recordingAttachmentEntity, $fromDate, $toDate)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['c'])
            ->from('GCRMCRMBundle:Client', 'a')
            ->leftJoin(
                $clientAndContractEntity,
                'b',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'a.id = b.client'
            )
            ->leftJoin(
                $contractEntity,
                'c',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'b.contract = c.id'
            )
            ->leftJoin(
                $recordingAttachmentEntity,
                'd',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'c.id = d.contract'
            )
            ->where('d.urlFileTemp IS NULL')
            ->andWhere('b.contract IS NOT NULL')
            ->andWhere('c.signDate >= :fromDate')
            ->andWhere('c.signDate <= :toDate')
            ->setParameters([
                'fromDate' => $fromDate,
                'toDate' => $toDate,
            ])
            ->getQuery()
        ;

        return $q->getResult();
    }
}