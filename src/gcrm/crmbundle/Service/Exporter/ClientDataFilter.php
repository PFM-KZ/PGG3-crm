<?php

namespace GCRM\CRMBundle\Service\Exporter;

use Doctrine\ORM\QueryBuilder;
use GCRM\CRMBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TZiebura\ExporterBundle\Service\DataFilter\DataFilterInterface;

class ClientDataFilter implements DataFilterInterface
{
    /** @var ContainerInterface $container */
    private $container;

    private $statusDepartments;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function addCriteria(Request $request, QueryBuilder $queryBuilder)
    {
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

        if ($dqlFilter) {
            $queryBuilder->where($dqlFilter);
        }
    }

    private function getStatusDepartments()
    {
        $this->statusDepartments = $this->container->get('doctrine.orm.entity_manager')->getRepository('GCRMCRMBundle:StatusDepartment')->findAll();
    }

    private function addParameters(Request $request, QueryBuilder $queryBuilder)
    {
        $plainParamsToBind = [
            'cSalesRepresentative' => 'lsSalesRepresentative',
            'cSignDateFrom' => 'lsSignDateFrom',
            'cSignDateTo' => 'lsSignDateTo',
            'cCreatedDateFrom' => 'lsCreatedDateFrom',
            'cCreatedDateTo' => 'lsCreatedDateTo',
            'entityPesel' => 'lsPesel',
            'entityTelephoneNr' => 'lsTelephoneNr',
            'entityName' => 'lsName',
            'entitySurname' => 'lsSurname',
            'entityNip' => 'lsNip',
            'entityBadgeId' => 'lsBadgeId',
            'cContractNumber' => 'lsContractNumber',
            'cContractType' => 'lsContractType',
        ];
        
        $likeParamsToBind = [
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
                ' (cwlr.isResignation != 1 AND cwlr.isBrokenContract != 1) ',
                ' (cmvno.isResignation != 1 AND cmvno.isBrokenContract != 1) ',
                ' (cfvno.isResignation != 1 AND cfvno.isBrokenContract != 1) ',
                ' (ctv.isResignation != 1 AND ctv.isBrokenContract != 1) ',
                ' (cint.isResignation != 1 AND cint.isBrokenContract != 1) ',
                ' (cpol.isResignation != 1 AND cpol.isBrokenContract != 1) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsSalesRepresentative')) {
            $tempOr = [
                ' (cwlr.salesRepresentative = :cSalesRepresentative) ',
                ' (cmvno.salesRepresentative = :cSalesRepresentative) ',
                ' (cfvno.salesRepresentative = :cSalesRepresentative) ',
                ' (ctv.salesRepresentative = :cSalesRepresentative) ',
                ' (cint.salesRepresentative = :cSalesRepresentative) ',
                ' (cpol.salesRepresentative = :cSalesRepresentative) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsContractNumber')) {
            $tempOr = [
                ' (cwlr.contractNumber = :cContractNumber) ',
                ' (cmvno.contractNumber = :cContractNumber) ',
                ' (cfvno.contractNumber = :cContractNumber) ',
                ' (ctv.contractNumber = :cContractNumber) ',
                ' (cint.contractNumber = :cContractNumber) ',
                ' (cpol.contractNumber = :cContractNumber) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsContractType')) {
            $tempOr = [
                ' (cwlr.type = :cContractType) ',
                ' (cmvno.type = :cContractType) ',
                ' (cfvno.type = :cContractType) ',
                ' (ctv.type = :cContractType) ',
                ' (cint.type = :cContractType) ',
                ' (cpol.type = :cContractType) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }








        $tempOr = null;
        $lsSignDateFrom = $request->query->get('lsSignDateFrom');
        $lsSignDateTo = $request->query->get('lsSignDateTo');
        if ($lsSignDateFrom && $lsSignDateTo) {
            $tempOr = [
                ' (cwlr.signDate >= :cSignDateFrom AND cwlr.signDate <= :cSignDateTo) ',
                ' (cmvno.signDate >= :cSignDateFrom AND cmvno.signDate <= :cSignDateTo) ',
                ' (cfvno.signDate >= :cSignDateFrom AND cfvno.signDate <= :cSignDateTo) ',
                ' (ctv.signDate >= :cSignDateFrom AND ctv.signDate <= :cSignDateTo) ',
                ' (cint.signDate >= :cSignDateFrom AND cint.signDate <= :cSignDateTo) ',
                ' (cpol.signDate >= :cSignDateFrom AND cpol.signDate <= :cSignDateTo) ',
            ];
        } elseif ($lsSignDateFrom) {
            $tempOr = [
                ' cwlr.signDate >= :cSignDateFrom ',
                ' cmvno.signDate >= :cSignDateFrom ',
                ' cfvno.signDate >= :cSignDateFrom ',
                ' ctv.signDate >= :cSignDateFrom ',
                ' cint.signDate >= :cSignDateFrom ',
                ' cpol.signDate >= :cSignDateFrom ',
            ];
        } elseif ($lsSignDateTo) {
            $tempOr = [
                ' cwlr.signDate <= :cSignDateTo ',
                ' cmvno.signDate <= :cSignDateTo ',
                ' cfvno.signDate <= :cSignDateTo ',
                ' ctv.signDate <= :cSignDateTo ',
                ' cint.signDate <= :cSignDateTo ',
                ' cpol.signDate <= :cSignDateTo ',
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
                ' (cwlr.createdAt >= :cCreatedDateFrom AND cwlr.createdAt <= :cCreatedDateTo) ',
                ' (cmvno.createdAt >= :cCreatedDateFrom AND cmvno.createdAt <= :cCreatedDateTo) ',
                ' (cfvno.createdAt >= :cCreatedDateFrom AND cfvno.createdAt <= :cCreatedDateTo) ',
                ' (ctv.createdAt >= :cCreatedDateFrom AND ctv.createdAt <= :cCreatedDateTo) ',
                ' (cint.createdAt >= :cCreatedDateFrom AND cint.createdAt <= :cCreatedDateTo) ',
                ' (cpol.createdAt >= :cCreatedDateFrom AND cpol.createdAt <= :cCreatedDateTo) ',
            ];
        } elseif ($lsCreatedDateFrom) {
            $tempOr = [
                ' cwlr.createdAt >= :cCreatedDateFrom ',
                ' cmvno.createdAt >= :cCreatedDateFrom ',
                ' cfvno.createdAt >= :cCreatedDateFrom ',
                ' ctv.createdAt >= :cCreatedDateFrom ',
                ' cint.createdAt >= :cCreatedDateFrom ',
                ' cpol.createdAt >= :cCreatedDateFrom ',
            ];
        } elseif ($lsCreatedDateTo) {
            $tempOr = [
                ' cwlr.createdAt <= :cCreatedDateTo ',
                ' cmvno.createdAt <= :cCreatedDateTo ',
                ' cfvno.createdAt <= :cCreatedDateTo ',
                ' ctv.createdAt <= :cCreatedDateTo ',
                ' cint.createdAt <= :cCreatedDateTo ',
                ' cpol.createdAt <= :cCreatedDateTo ',
            ];
        }
        if ($tempOr && is_array($tempOr)) {
            $tempOrQueryPart = implode(' OR ', $tempOr);
            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }



        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' c.pesel = :entityPesel';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' c.telephoneNr = :entityTelephoneNr';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' c.name = :entityName';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' c.surname = :entitySurname';
        }
        if ($request->query->get('lsNip')) {
            $dqlAnd[] = ' c.nip = :entityNip';
        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' c.badgeId = :entityBadgeId';
        }


        $statusDepartmentId = $request->query->get('lsStatusDepartment');

        $statusContractId = $request->query->get('lsStatusContract');
        if ($statusDepartmentId && is_numeric($statusDepartmentId)) {
            if ($statusContractId && is_numeric($statusContractId)) {
                $contractVariableName = $this->statusContractVariableNameByDepartment($this->statusDepartments, $statusDepartmentId);
                $dqlOr[] = ' cwlr.statusDepartment = ' . $statusDepartmentId . ' AND cwlr.statusContract' . $contractVariableName . ' = ' . $statusContractId;
                $dqlOr[] = ' cmvno.statusDepartment = ' . $statusDepartmentId . ' AND cmvno.statusContract' . $contractVariableName . ' = ' . $statusContractId;
                $dqlOr[] = ' cfvno.statusDepartment = ' . $statusDepartmentId . ' AND cfvno.statusContract' . $contractVariableName . ' = ' . $statusContractId;
                $dqlOr[] = ' ctv.statusDepartment = ' . $statusDepartmentId . ' AND ctv.statusContract' . $contractVariableName . ' = ' . $statusContractId;
                $dqlOr[] = ' cint.statusDepartment = ' . $statusDepartmentId . ' AND cint.statusContract' . $contractVariableName . ' = ' . $statusContractId;
                $dqlOr[] = ' cpol.statusDepartment = ' . $statusDepartmentId . ' AND cpol.statusContract' . $contractVariableName . ' = ' . $statusContractId;
            } else {
                $dqlOr[] = ' cwlr.statusDepartment = ' . $statusDepartmentId . ' ';
                $dqlOr[] = ' cmvno.statusDepartment = ' . $statusDepartmentId . ' ';
                $dqlOr[] = ' cfvno.statusDepartment = ' . $statusDepartmentId . ' ';
                $dqlOr[] = ' ctv.statusDepartment = ' . $statusDepartmentId . ' ';
                $dqlOr[] = ' cint.statusDepartment = ' . $statusDepartmentId . ' ';
                $dqlOr[] = ' cpol.statusDepartment = ' . $statusDepartmentId . ' ';
            }
        } elseif ($statusContractId && is_numeric($statusContractId)) {
            $dqlOr[] = $this->statusContractQueryByContractCode('cwlr', $statusContractId);
            $dqlOr[] = $this->statusContractQueryByContractCode('cmvno', $statusContractId);
            $dqlOr[] = $this->statusContractQueryByContractCode('cfvno', $statusContractId);
            $dqlOr[] = $this->statusContractQueryByContractCode('ctv', $statusContractId);
            $dqlOr[] = $this->statusContractQueryByContractCode('cint', $statusContractId);
            $dqlOr[] = $this->statusContractQueryByContractCode('cpol', $statusContractId);
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