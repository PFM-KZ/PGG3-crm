<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use TZiebura\ExporterBundle\Service\DataFilter\DataFilterInterface;

class DebitNoteDataFilter implements DataFilterInterface
{
    public function addCriteria(Request $request, QueryBuilder $queryBuilder)
    {
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

        if($dqlFilter) {
            $queryBuilder->where($dqlFilter);
        }
    }

    public function addParameters(Request $request, QueryBuilder $queryBuilder)
    {
        $plainParamsToBind = [
            'lsCreatedDateFrom' => 'lsCreatedDateFrom',
            'lsCreatedDateTo' => 'lsCreatedDateTo',
            'lsDateOfPaymentFrom' => 'lsDateOfPaymentFrom',
            'lsDateOfPaymentTo' => 'lsDateOfPaymentTo',
            'lsIsPaid' => 'lsIsPaid',
            'lsPesel' => 'lsPesel',
            'lsName' => 'lsName',
            'lsSurname' => 'lsSurname',
            'lsNip' => 'lsNip',
            'lsBadgeId' => 'lsBadgeId',
            'lsTelephoneNr' => 'lsTelephoneNr',
            'lsContractNumber' => 'lsContractNumber',
        ];

        foreach($plainParamsToBind as $queryParam => $requestParam) {
            $this->bindParam($request, $queryBuilder, $queryParam, $requestParam);
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
        $lsCreatedDateFrom = $request->query->get('lsCreatedDateFrom');
        $lsCreatedDateTo = $request->query->get('lsCreatedDateTo');
        $lsDateOfPaymentFrom = $request->query->get('lsDateOfPaymentFrom');
        $lsDateOfPaymentTo = $request->query->get('lsDateOfPaymentTo');

        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' d.clientPesel = :lsPesel';
        }
        if ($request->query->get('lsContractNumber')) {
            $dqlAnd[] = ' d.contractNumber = :lsContractNumber';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' d.clientName = :lsName';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' d.clientSurname = :lsSurname';
        }
        if ($request->query->get('lsNip')) {
            $dqlAnd[] = ' d.clientNip = :lsNip';
        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' d.badgeId = :lsBadgeId';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' c.telephoneNr = :lsTelephoneNr';
        }

        if ($lsCreatedDateFrom) {
            $dqlAnd[] = ' d.createdDate >= :lsCreatedDateFrom';
        }
        if ($lsCreatedDateTo) {
            $dqlAnd[] = ' d.createdDate <= :lsCreatedDateTo';
        }

        if ($lsDateOfPaymentFrom) {
            $dqlAnd[] = ' d.dateOfPayment >= :lsDateOfPaymentFrom';
        }
        if ($lsDateOfPaymentTo) {
            $dqlAnd[] = ' d.dateOfPayment <= :lsDateOfPaymentTo';
        }
    }
}