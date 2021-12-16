<?php

namespace GCRM\CRMBundle\Service\Exporter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TZiebura\ExporterBundle\Service\DataFilter\DataFilterInterface;

class InvoiceDataFilter implements DataFilterInterface
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
            'lsInvoiceCreatedDateFrom' => 'lsInvoiceCreatedDateFrom',
            'lsInvoiceCreatedDateTo' => 'lsInvoiceCreatedDateTo',
            'lsInvoiceNumber' => 'lsInvoiceNumber',
            'lsPesel' => 'lsPesel',
            'lsName' => 'lsName',
            'lsSurname' => 'lsSurname',
            'lsNip' => 'lsNip',
            'lsBadgeId' => 'lsBadgeId',
            'lsTelephoneNr' => 'lsTelephoneNr',
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
        $lsInvoiceCreatedDateFrom = $request->query->get('lsInvoiceCreatedDateFrom');
        $lsInvoiceCreatedDateTo = $request->query->get('lsInvoiceCreatedDateTo');

        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' c.pesel = :lsPesel';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' c.name = :lsName';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' c.surname = :lsSurname';
        }
        if ($request->query->get('lsNip')) {
            $dqlAnd[] = ' c.nip = :lsNip';
        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' c.badgeId = :lsBadgeId';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' c.telephoneNr = :lsTelephoneNr';
        }


        if ($lsInvoiceCreatedDateFrom) {
            $dqlAnd[] = ' i.createdDate >= :lsInvoiceCreatedDateFrom';
        }

        if ($lsInvoiceCreatedDateTo) {
            $dqlAnd[] = ' i.createdDate <= :lsInvoiceCreatedDateTo';
        }

        if ($request->query->get('lsInvoiceNumber')) {
            $dqlAnd[] = ' i.number = :lsInvoiceNumber';
        }
    }
}