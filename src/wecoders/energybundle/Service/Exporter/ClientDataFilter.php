<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use Doctrine\ORM\QueryBuilder;
use GCRM\CRMBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TZiebura\ExporterBundle\Service\DataFilter\DataFilterInterface;

class ClientDataFilter implements DataFilterInterface
{
    /** @var ContainerInterface $container */
    private $container;

    /** @var User $user */
    private $user;

    private $userBranches;
    private $statusDepartments;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function addCriteria(Request $request, QueryBuilder $queryBuilder)
    {
        $this->getUserBranches();
        $this->getStatusDepartments();

        $this->addParameters($request, $queryBuilder);

        $dqlOr = array();
        $dqlAnd = array();

        $this->addQuery($request, $dqlAnd, $dqlOr);

        $dqlFilter = '';
        if (count($dqlOr)) {
            $dqlFilter .= count($dqlOr) > 1 ? '(' : '';
            $dqlFilter .= implode(' OR ', $dqlOr);
            $dqlFilter .= count($dqlOr) > 1 ? ')' : '';

        }

        if (count($dqlAnd)) {
            if (count($dqlOr)) {
                $dqlFilter .= ' AND ';
            }
            $dqlFilter .= implode(' AND ', $dqlAnd);
        }

        $queryBuilder->where($dqlFilter);
    }

    private function getUserBranches()
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $branches = [];
        $userAndBranches = $this->user->getUserAndBranches();
        foreach ($userAndBranches as $userAndBranch) {
            $branch = $userAndBranch->getBranch();
            if (!$branch) {
                continue;
            }
            $branches[] = $branch;
        }

        // user can have more branches than are set somehow, to avoid that choose branches from db
        if (count($branches)) {
            $branches = $this->container->get('doctrine.orm.entity_manager')->getRepository('GCRMCRMBundle:Branch')->findBy(['id' => $branches]);
        }

        $this->userBranches = $branches;
    }

    private function getStatusDepartments()
    {
        $this->statusDepartments = $this->container->get('doctrine.orm.entity_manager')->getRepository('GCRMCRMBundle:StatusDepartment')->findAll();
    }

    private function addParameters(Request $request, QueryBuilder $queryBuilder)
    {
        $ids = [];
        foreach ($this->userBranches as $userBranch) {
            $ids[] = $userBranch->getId();
        }

        if ($request->query->get('lsBranch')) {
            $ids = in_array($request->query->get('lsBranch'), $ids) ? [$request->query->get('lsBranch')] : $ids;
        }

        $queryBuilder->setParameter('jBranch', $ids);

        $plainParamsToBind = [
            'cBrand' => 'lsBrand',
            'cSalesRepresentative' => 'lsSalesRepresentative',
            'cSignDateFrom' => 'lsSignDateFrom',
            'cSignDateTo' => 'lsSignDateTo',
            'cCreatedDateFrom' => 'lsCreatedDateFrom',
            'cCreatedDateTo' => 'lsCreatedDateTo',
            'cContractType' => 'lsContractType',
        ];
        
        $likeParamsToBind = [
            'entityPesel' => 'lsPesel',
            'entityTelephoneNr' => 'lsTelephoneNr',
            'entityName' => 'lsName',
            'entitySurname' => 'lsSurname',
            'entityNip' => 'lsNip',
            'entityBadgeId' => 'lsBadgeId',
            'cContractNumber' => 'lsContractNumber',
            'ppCode' => 'ppCode',
        ];
        
        foreach($plainParamsToBind as $queryParam => $requestParam) {
            $this->bindParam($request, $queryBuilder, $queryParam, $requestParam);
        }

        foreach($likeParamsToBind as $queryParam => $requestParam) {
            $this->bindParam($request, $queryBuilder, $queryParam, $requestParam, true);
        }
    }

    private function bindParam(Request $request, QueryBuilder $queryBuilder, $queryParam, $requestParam, $incLike = false)
    {
        if($incLike) {
            if ($request->query->get($requestParam)) {
                $queryBuilder->setParameter($queryParam, '%' . $request->query->get($requestParam) . '%');
            }
        } else {
            if ($request->query->get($requestParam)) {
                $queryBuilder->setParameter($queryParam, $request->query->get($requestParam));
            }
        }
    }

    private function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [])
    {
        if ($request->query->get('lsHideNotActual')) {
            $tempOr = [
                ' (cg.isResignation != 1) ',
                ' (ce.isResignation != 1) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';

            $tempOr = [
                ' (cg.isBrokenContract != 1) ',
                ' (ce.isBrokenContract != 1) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsBrand')) {
            $tempOr = [
                ' (cg.brand = :cBrand) ',
                ' (ce.brand= :cBrand) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsSalesRepresentative')) {
            $tempOr = [
                ' (cg.salesRepresentative = :cSalesRepresentative) ',
                ' (ce.salesRepresentative = :cSalesRepresentative) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsBranch')) {
            $tempOr = [
                ' uab.branch = :jBranch',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        } else {
            // lsBranch is not choosen so show contracts from all branches of that user
            $dqlAnd[] = '(uab.branch IN (:jBranch))';
        }

        if ($request->query->get('lsContractNumber')) {
            $tempOr = [
                ' (cg.contractNumber LIKE :cContractNumber) ',
                ' (ce.contractNumber LIKE :cContractNumber) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsContractType')) {
            $tempOr = [
                ' (cg.type = :cContractType) ',
                ' (ce.type = :cContractType) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }


        $tempOr = null;
        $lsSignDateFrom = $request->query->get('lsSignDateFrom');
        $lsSignDateTo = $request->query->get('lsSignDateTo');
        if ($lsSignDateFrom && $lsSignDateTo) {
            $tempOr = [
                ' (cg.signDate >= :cSignDateFrom AND cg.signDate <= :cSignDateTo) ',
                ' (ce.signDate >= :cSignDateFrom AND ce.signDate <= :cSignDateTo) ',
            ];
        } elseif ($lsSignDateFrom) {
            $tempOr = [
                ' cg.signDate >= :cSignDateFrom ',
                ' ce.signDate >= :cSignDateFrom ',
            ];
        } elseif ($lsSignDateTo) {
            $tempOr = [
                ' cg.signDate <= :cSignDateTo ',
                ' ce.signDate <= :cSignDateTo ',
            ];
        }
        if ($tempOr && is_array($tempOr)) {
            $tempOrQueryPart = implode(' OR ', $tempOr);
            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }



        $tempOr = null;
        $lsCreatedDateFrom = $request->query->get('lsCreatedDateFrom');
        $lsCreatedDateTo = $request->query->get('lsCreatedDateTo');
        if ($lsCreatedDateFrom && $lsCreatedDateTo) {
            $tempOr = [
                ' (cg.createdAt >= :cCreatedDateFrom AND cg.createdAt <= :cCreatedDateTo) ',
                ' (ce.createdAt >= :cCreatedDateFrom AND ce.createdAt <= :cCreatedDateTo) ',
            ];
        } elseif ($lsCreatedDateFrom) {
            $tempOr = [
                ' cg.createdAt >= :cCreatedDateFrom ',
                ' ce.createdAt >= :cCreatedDateFrom ',
            ];
        } elseif ($lsCreatedDateTo) {
            $tempOr = [
                ' cg.createdAt <= :cCreatedDateTo ',
                ' ce.createdAt <= :cCreatedDateTo ',
            ];
        }
        if ($tempOr && is_array($tempOr)) {
            $tempOrQueryPart = implode(' OR ', $tempOr);
            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }


        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' (c.pesel LIKE :entityPesel OR cg.secondPersonPesel LIKE :entityPesel OR ce.secondPersonPesel LIKE :entityPesel)';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' c.telephoneNr LIKE :entityTelephoneNr';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' (c.name LIKE :entityName OR cg.secondPersonName LIKE :entityName OR ce.secondPersonName LIKE :entityName)';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' (c.surname LIKE :entitySurname OR cg.secondPersonSurname LIKE :entitySurname OR ce.secondPersonSurname LIKE :entitySurname)';
        }
        if ($request->query->get('lsNip')) {
            $dqlAnd[] = ' c.nip LIKE :entityNip';
        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' c.badgeId LIKE :entityBadgeId';
        }
        if ($request->query->get('ppCode')) {
            $dqlAnd[] = ' (ceappc.ppCode LIKE :ppCode OR cgappc.ppCode LIKE :ppCode )';
        }

        $statusDepartmentId = $request->query->get('lsStatusDepartment');
        $statusContractId = $request->query->get('lsStatusContract');
        $statusDepartmentStatusId = $request->query->get('lsStatusDepartmentStatus');
        $actualStatusId = $request->query->get('lsActualStatus');

        if ($statusDepartmentId && is_numeric($statusDepartmentId)) {
            $tempOr = [
                ' cg.statusDepartment = ' . $statusDepartmentId . ' ',
                ' ce.statusDepartment = ' . $statusDepartmentId . ' ',
            ];
            if ($tempOr && is_array($tempOr)) {
                $tempOrQueryPart = implode(' OR ', $tempOr);
                $dqlAnd[] = '(' . $tempOrQueryPart . ')';
            }
        }

        if ($actualStatusId && is_numeric($actualStatusId)) {
            $tempOr = [
                ' cg.actualStatus = ' . $actualStatusId . ' ',
                ' ce.actualStatus = ' . $actualStatusId . ' ',
            ];
            if ($tempOr && is_array($tempOr)) {
                $tempOrQueryPart = implode(' OR ', $tempOr);
                $dqlAnd[] = '(' . $tempOrQueryPart . ')';
            }
        }

        if ($statusDepartmentStatusId && is_numeric($statusDepartmentStatusId) && $statusContractId && is_numeric($statusContractId)) {
            $contractVariableName = $this->statusContractVariableNameByDepartment($this->statusDepartments, $statusDepartmentStatusId);
            $dqlOr[] = ' cg.statusContract' . $contractVariableName . ' = ' . $statusContractId;
            $dqlOr[] = ' ce.statusContract' . $contractVariableName . ' = ' . $statusContractId;
        } elseif ($statusContractId && is_numeric($statusContractId)) {
            $dqlOr[] = $this->statusContractQueryByContractCode('cg', $statusContractId);
            $dqlOr[] = $this->statusContractQueryByContractCode('ce', $statusContractId);
        }
    }

    private function statusContractVariableNameByDepartment($statusDepartments, $statusDepartmentId)
    {
        $result = null;

        /** @var StatusDepartment $department */
        foreach ($statusDepartments as $department) {
            if ($department->getId() == $statusDepartmentId) {
                /** @var StatusDepartment $result */
                $result = $department;
            }
        }

        if (!$result) {
            die('Wybrany departament nie istnieje');
        }

        $code = $result->getCode();
        $variableName = null;
        if ($code == 'finances') {
            $variableName = 'Finances';
        } elseif ($code == 'process') {
            $variableName = 'Process';
        } elseif ($code == 'control') {
            $variableName = 'Control';
        } elseif ($code == 'verification') {
            $variableName = 'Verification';
        } elseif ($code == 'administration') {
            $variableName = 'Administration';
        }

        if (!$variableName) {
            die('Statusy działów są błędnie zdefiniowane');
        }

        return $variableName;
    }

    private function statusContractQueryByContractCode($contractCode, $statusContractId)
    {
        $departments = [
            'Finances',
            'Process',
            'Control',
            'Verification',
            'Administration'
        ];

        $dqlOr = [];
        foreach ($departments as $department) {
            $dqlOr[] = ' ' . $contractCode . '.statusContract' . $department . ' = ' . $statusContractId;
        }

        return implode(' OR ', $dqlOr);
    }
}