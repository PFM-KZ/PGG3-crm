<?php

namespace GCRM\CRMBundle\Service\Exporter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TZiebura\ExporterBundle\Service\DataFilter\DataFilterInterface;

class ClientEnquiryDataFilter implements DataFilterInterface
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
            'lsUser' => 'lsUser',
            'lsDateFrom' => 'lsDateFrom',
            'lsDateTo' => 'lsDateTo',
            'lsName' => 'lsName',
            'lsSurname' => 'lsSurname',
            'lsPesel' => 'lsPesel',
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
        $lsDateFrom = $request->query->get('lsDateFrom');
        $lsDateTo = $request->query->get('lsDateTo');


        if ($request->query->get('lsUser')) {
            $dqlAnd[] = ' sr.id = :lsUser';
        }
        if ($lsDateFrom) {
            $dqlAnd[] = ' i.createdAt >= :lsDateFrom';
        }
        if ($lsDateTo) {
            $dqlAnd[] = ' i.createdAt <= :lsDateTo';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' i.name = :lsName';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' i.surname = :lsSurname';
        }
        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' i.pesel = :lsPesel';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' i.telephoneNr = :lsTelephoneNr';
        }
    }
}