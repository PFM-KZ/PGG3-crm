<?php

namespace GCRM\CRMBundle\Service;

use Complex\Exception;
use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ChangeStatusLog;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\ContractInterface;
use GCRM\CRMBundle\Entity\StatusContract;
use GCRM\CRMBundle\Entity\StatusContractAndSpecialAction;
use GCRM\CRMBundle\Entity\StatusDepartment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ContractModel
{
    /** @var  EntityManager */
    private $em;

    private $container;

    private $energySystemTables = [
        'GCRMCRMBundle:ClientAndContractGas' => 'GCRMCRMBundle:ContractGas',
        'GCRMCRMBundle:ClientAndContractEnergy' => 'GCRMCRMBundle:ContractEnergy',
    ];

    private $hierarchyDepartments = [
        'AuthorizationDepartment' => [
            'index' => 0,
            'getStatusMethodName' => 'getStatusContractAuthorization',
            'statusDepartmentCode' => 'authorization'
        ],
        'VerificationDepartment' => [
            'index' => 1,
            'getStatusMethodName' => 'getStatusContractVerification',
            'statusDepartmentCode' => 'verification'
        ],
        'AdministrationDepartment' => [
            'index' => 2,
            'getStatusMethodName' => 'getStatusContractAdministration',
            'statusDepartmentCode' => 'administration'
        ],
        'ControlDepartment' => [
            'index' => 3,
            'getStatusMethodName' => 'getStatusContractControl',
            'statusDepartmentCode' => 'control'
        ],
        'ProcessDepartment' => [
            'index' => 4,
            'getStatusMethodName' => 'getStatusContractProcess',
            'statusDepartmentCode' => 'process'
        ],
        'FinancesDepartment' => [
            'index' => 5,
            'getStatusMethodName' => 'getStatusContractFinances',
            'statusDepartmentCode' => 'finances'
        ]
    ];

    /**
     * @return array
     */
    public function getHierarchyDepartments()
    {
        return $this->hierarchyDepartments;
    }

    public function __construct(EntityManager $em, ContainerInterface $container, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->container = $container;
        $this->tokenStorage = $tokenStorage;
    }

    public function getActualStatus($contract)
    {
        if (!$contract) {
            return null;
        }

        $currentStatusDepartment = $contract->getStatusDepartment();
        // check backward
        $flippedDataDepartments = array_reverse($this->hierarchyDepartments);
        $canCheck = false;
        foreach ($flippedDataDepartments as $departmentData) {
            // if found current department start check from it
            if ($departmentData['statusDepartmentCode'] == $currentStatusDepartment->getCode()) {
                $canCheck = true;
            }

            if (!$canCheck) {
                continue;
            }

            $status = $contract->{$departmentData['getStatusMethodName']}();
            if ($status) {
                return $status->getTitle();
            }
        }

        return null;
    }

    public function manageActualStatus($contract)
    {
        if (!$contract) {
            return null;
        }

        $currentStatusDepartment = $contract->getStatusDepartment();
        // check backward
        $flippedDataDepartments = array_reverse($this->hierarchyDepartments);
        $canCheck = false;
        foreach ($flippedDataDepartments as $departmentData) {
            // if found current department start check from it
            if ($departmentData['statusDepartmentCode'] == $currentStatusDepartment->getCode()) {
                $canCheck = true;
            }

            if (!$canCheck) {
                continue;
            }

            $status = $contract->{$departmentData['getStatusMethodName']}();
            if ($status) {
                return $status;
            }
        }

        return null;
    }

    public function getDefaultContactTablesData()
    {
        return $this->energySystemTables;
    }

    public function getContractByNumber($number, $fromTables = null)
    {
        $fromTables = $fromTables ?: $this->getDefaultContactTablesData();

        if (!is_array($fromTables)) {
            return null;
        }

        foreach ($fromTables as $linkTable => $contractTable) {
            $qb = $this->em->createQueryBuilder();
            $q = $qb->select(['a'])
                ->from($contractTable, 'a')
                ->leftJoin(
                    $linkTable,
                    'b',
                    'WITH',
                    'b.contract = a.id'
                )
                ->where('a.contractNumber = :number')
                ->andWhere('b.contract IS NOT NULL')
                ->andWhere('b.client IS NOT NULL')
                ->setParameters([
                    'number' => $number
                ])
                ->getQuery()
            ;

            $contract = $q->getResult();
            if ($contract && count($contract)) {
                return $contract[0];
            }
        }

        return null;
    }

    public function getClientAndContractBy($property, $value, $client = null, $searchIn = 'contract', $fromTables = null)
    {
        $fromTables = $fromTables ?: $this->getDefaultContactTablesData();

        if (!is_array($fromTables)) {
            return null;
        }

        foreach ($fromTables as $linkTable => $contractTable) {
            $qb = $this->em->createQueryBuilder();
            $q = $qb->select(['a'])
                ->from($linkTable, 'a')
                ->leftJoin(
                    $contractTable,
                    'contract',
                    'WITH',
                    'contract.id = a.contract'
                )
                ->leftJoin(
                    'GCRMCRMBundle:Client',
                    'client',
                    'WITH',
                    'client.id = a.client'
                )
                ->leftJoin(
                    'GCRMCRMBundle:AccountNumberIdentifier',
                    'accountNumberIdentifier',
                    'WITH',
                    'client.accountNumberIdentifier = accountNumberIdentifier.id'
                );

            if ($searchIn == 'contractAndPpCode') {
                if ($contractTable == 'GCRMCRMBundle:ContractGas') {
                    $contractAndPpCodeTable = 'GCRMCRMBundle:ContractGasAndPpCode';
                } else {
                    $contractAndPpCodeTable = 'GCRMCRMBundle:ContractEnergyAndPpCode';
                }

                $q->leftJoin(
                    $contractAndPpCodeTable,
                    'contractAndPpCode',
                    'WITH',
                    'contract.id = contractAndPpCode.contract'
                );
            }

            $parameters = [
                $property => $value
            ];

            $q = $q->where($searchIn . '.' . $property . ' = :' . $property);

            if ($client) {
                $q = $q->andWhere('client.id = :clientId');
                $parameters['clientId'] = $client;
            }

            $q = $q
                ->andWhere('a.contract IS NOT NULL')
                ->andWhere('a.client IS NOT NULL')
                ->setParameters($parameters)
                ->getQuery()
            ;

            $clientAndContracts = $q->getResult();
            if ($clientAndContracts && count($clientAndContracts)) {
                return $clientAndContracts[0];
            }
        }

        return null;
    }

    public function getContractBy($property, $value, $searchIn = 'contract', $fromTables = null)
    {
        $fromTables = $fromTables ?: $this->getDefaultContactTablesData();

        if (!is_array($fromTables)) {
            return null;
        }

        foreach ($fromTables as $linkTable => $contractTable) {
            $qb = $this->em->createQueryBuilder();
            $q = $qb->select(['contract'])
                ->from($contractTable, 'contract')
                ->leftJoin(
                    $linkTable,
                    'b',
                    'WITH',
                    'b.contract = contract.id'
                )
                ->leftJoin(
                    'GCRMCRMBundle:Client',
                    'client',
                    'WITH',
                    'client.id = b.client'
                )
                ->where($searchIn . '.' . $property . ' = :' . $property)
                ->andWhere('b.contract IS NOT NULL')
                ->andWhere('b.client IS NOT NULL')
                ->setParameters([
                    $property => $value
                ])
                ->getQuery()
            ;

            $contract = $q->getResult();
            if ($contract && count($contract)) {
                return $contract[0];
            }
        }

        return null;
    }

    public function hasContractEndStatus(ContractEnergyBase $contract)
    {
        /** @var StatusContract $statusContract */
        $statusContract = $contract->getStatusContractFinances();
        if (!$statusContract) {
            return false;
        }

        $specialActions = $statusContract->getSpecialActions();
        /** @var StatusContractAndSpecialAction $specialAction */
        foreach ($specialActions as $specialAction) {
            if ($specialAction->getOption() == StatusContractModel::SPECIAL_ACTION_CONTRACT_END) {
                return true;
            }
        }

        return false;
    }

    public function getContracts($fromTables = [
        'GCRMCRMBundle:ClientAndContractGas' => 'GCRMCRMBundle:ContractGas',
        'GCRMCRMBundle:ClientAndContractEnergy' => 'GCRMCRMBundle:ContractEnergy',
    ])
    {
        if (!is_array($fromTables)) {
            return null;
        }

        $contracts = [];

        foreach ($fromTables as $linkTable => $contractTable) {
            $qb = $this->em->createQueryBuilder();
            $q = $qb->select(['a'])
                ->from($contractTable, 'a')
                ->leftJoin(
                    $linkTable,
                    'b',
                    'WITH',
                    'b.contract = a.id'
                )
                ->where('b.contract IS NOT NULL')
                ->andWhere('b.client IS NOT NULL')
                ->getQuery()
            ;

            $result = $q->getResult();
            if ($result && count($result)) {
                $contracts = array_merge($contracts, $result);
            }
        }

        if ($contracts && count($contracts)) {
            return $contracts;
        }
        return null;
    }

    public function addLog(ContractEnergyBase $contractBeforeChanges, ContractEnergyBase $contract)
    {
        foreach ($this->hierarchyDepartments as $departmentName => $value) {
            if ($contract->getStatusDepartment()->getCode() != $value['statusDepartmentCode']) {
                continue;
            }

            $status = null;
            if (method_exists($contract, $value['getStatusMethodName'])) {
                $status = $contract->{$value['getStatusMethodName']}();
            }

            // ADD LOG
            // check if status was changed - check before -> and now
            $beforeStatus = $contractBeforeChanges->{$value['getStatusMethodName']}();
            if ($beforeStatus != $status) {
                // status changes -> adds log
                $changeStatusLog = new ChangeStatusLog();
                $changeStatusLog->setContractNumber($contract->getContractNumber());
                $changeStatusLog->setFromStatus($beforeStatus);
                $changeStatusLog->setToStatus($status);
                $changeStatusLog->setDepartment($this->em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy(['code' => $value['statusDepartmentCode']]));
                $changeStatusLog->setChangedBy($this->tokenStorage->getToken()->getUser());
                $this->em->persist($changeStatusLog);
                $this->em->flush();
            }

            break;
        }
    }

    public function onPostUpdate(ContractEnergyBase $contract)
    {
        // check if client resigned from contract 14 days resignation
        $isResignation = $this->checkIfContractHaveResignationStatus($contract);
        if ($isResignation) {
            $contract->setIsResignation($isResignation);
        }

        // check if client broken contract
        $isBrokenContract = $this->checkIfContractHaveBrokenStatus($contract);
        if ($isBrokenContract) {
            $contract->setIsBrokenContract($isBrokenContract);
        }

        $status = null;
        $beforeDepartmentActualStatus = null;
        foreach ($this->hierarchyDepartments as $departmentName => $value) {
            if (method_exists($contract, $value['getStatusMethodName'])) {
                $status = $contract->{$value['getStatusMethodName']}();
            }

            if ($contract->getStatusDepartment()->getCode() != $value['statusDepartmentCode']) {
                $beforeDepartmentActualStatus = $status;
                continue;
            }

            $statusAction = null;
            if ($status) {
                $statusAction = $status->getStatusContractAction();
            } else {
                $status = $beforeDepartmentActualStatus;
                // status is empty, so actual status is a status from department before
            }

            if (
                !$isResignation && !$isBrokenContract &&
                $statusAction && $statusAction->getCode()
            ) {
                $code = $statusAction->getCode();

                if ($code == 'GO' || $code == 'BACK') {
                    $currentIndex = $value['index'];

                    // in situation that contract go on package list do not update status department
                    $canUpdateStatusDepartment = true;
                    if ($code == 'GO') {
                        $nextIndex = $currentIndex + 1;

                        // check if next department is control department,
                        // if so, contract need to go to package list
                        $nextIsControlDepartment = $this->checkIfIndexBelongsToDepartment($nextIndex, 'ControlDepartment');
                        if ($nextIsControlDepartment) {
                            $contract->setIsOnPackageList(true);
                            $this->em->persist($contract);

                            $canUpdateStatusDepartment = false;
                        }
                    } else {
                        $nextIndex = $currentIndex - 1;
                    }

                    if ($canUpdateStatusDepartment) {
                        $newStatusDepartmentCode = null;
                        foreach ($this->hierarchyDepartments as $item) {
                            if ($item['index'] == $nextIndex) {
                                $newStatusDepartmentCode = $item['statusDepartmentCode'];
                            }
                        }
                        if ($newStatusDepartmentCode) {
                            $newStatusDepartment = $this->em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy(['code' => $newStatusDepartmentCode]);
                            $contract->setStatusDepartment($newStatusDepartment);
                            $this->em->persist($contract);
                        }
                    }
                } elseif ($code == 'STAY') {
                    // do nothing
                }
            }

            if ($status) {
                $contract->setActualStatus($status);
                $this->em->flush();
            }
            break;
        }
    }

    private function checkIfIndexBelongsToDepartment($nextIndex, $departmentName)
    {
        foreach ($this->hierarchyDepartments as $key => $item) {
            if ($item['index'] == $nextIndex && $key == $departmentName) {
                return true;
            }
        }

        return false;
    }

    private function checkIfContractHaveResignationStatus(ContractInterface $contract)
    {
        $resignationStatuses = [
            'RESIGN',
        ];

        $statuses = [
            $contract->getStatusContractAuthorization(),
            $contract->getStatusContractVerification(),
            $contract->getStatusContractAdministration(),
            $contract->getStatusContractControl(),
            $contract->getStatusContractProcess(),
            $contract->getStatusContractFinances(),
        ];

        foreach ($statuses as $status) {
            if (!$status) {
                continue;
            }

            /** @var StatusContractAction $statusAction */
            $statusAction = $status->getStatusContractAction();
            if (!$statusAction) {
                continue;
            }

            if ($status && in_array($statusAction->getCode(), $resignationStatuses)) {
                return true;
            }
        }

        return false;
    }

    private function checkIfContractHaveBrokenStatus(ContractInterface $contract)
    {
        $resignationStatuses = [
            'BROKE',
        ];

        $statuses = [
            $contract->getStatusContractAuthorization(),
            $contract->getStatusContractVerification(),
            $contract->getStatusContractAdministration(),
            $contract->getStatusContractControl(),
            $contract->getStatusContractProcess(),
            $contract->getStatusContractFinances(),
        ];

        foreach ($statuses as $status) {
            if (!$status) {
                continue;
            }

            /** @var StatusContractAction $statusAction */
            $statusAction = $status->getStatusContractAction();
            if (!$statusAction) {
                continue;
            }

            if ($status && in_array($statusAction->getCode(), $resignationStatuses)) {
                return true;
            }
        }

        return false;
    }

}