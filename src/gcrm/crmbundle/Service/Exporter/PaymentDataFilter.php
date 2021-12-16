<?php

namespace GCRM\CRMBundle\Service\Exporter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TZiebura\ExporterBundle\Service\DataFilter\DataFilterInterface;

class PaymentDataFilter implements DataFilterInterface
{
    /** @var ContainerInterface $container */
    private $container;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
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
            'lsCreatedAtFrom' => 'lsCreatedAtFrom',
            'lsCreatedAtTo' => 'lsCreatedAtTo',
            'lsDateFrom' => 'lsDateFrom',
            'lsDateTo' => 'lsDateTo',
            'lsValueFrom' => 'lsValueFrom',
            'lsValueTo' => 'lsValueTo',
        ];
        
        $likeParamsToBind = [
            'lsPesel' => 'lsPesel',
            'lsTelephoneNr' => 'lsTelephoneNr',
            'lsName' => 'lsName',
            'lsSurname' => 'lsSurname',
            'lsNip' => 'lsNip',
            'lsSenderAccountNumber' => 'lsSenderAccountNumber',
            'lsBadgeId' => 'lsBadgeId',
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
        if ($request->query->get('lsCreatedAtFrom')) {
            $dqlAnd[] = ' p.createdAt >= :lsCreatedAtFrom';
        }
        if ($request->query->get('lsCreatedAtTo')) {
            $dqlAnd[] = ' p.createdAt <= :lsCreatedAtTo';
        }
        if ($request->query->get('lsDateFrom')) {
            $dqlAnd[] = ' p.date >= :lsDateFrom';
        }
        if ($request->query->get('lsDateTo')) {
            $dqlAnd[] = ' p.date <= :lsDateTo';
        }
        if ($request->query->get('lsValueFrom')) {
            $dqlAnd[] = ' p.value >= :lsValueFrom';
        }
        if ($request->query->get('lsValueTo')) {
            $dqlAnd[] = ' p.value <= :lsValueTo';
        }
        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' c.pesel LIKE :lsPesel';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' c.telephoneNr LIKE :lsTelephoneNr';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' c.name LIKE :lsName';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' c.surname LIKE :lsSurname';
        }
        if ($request->query->get('lsNip')) {
            $dqlAnd[] = ' c.nip LIKE :lsNip';
        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' p.badgeId LIKE :lsBadgeId';
        }
        if ($request->query->get('lsSenderAccountNumber')) {
            $dqlAnd[] = ' p.senderAccountNumber LIKE :lsSenderAccountNumber';
        }
    }
}