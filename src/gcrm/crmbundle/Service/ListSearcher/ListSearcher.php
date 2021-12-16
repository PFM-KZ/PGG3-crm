<?php

namespace GCRM\CRMBundle\Service\ListSearcher;

use Doctrine\ORM\QueryBuilder;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

abstract class ListSearcher
{
    /** @var  ContractModel */
    protected $contractModel;

    /** @var  Request */
    protected $request;

    /** @var  ContainerInterface */
    protected $container;

    protected $joinTables;

    protected $fromTables = [];

    protected $exporterTableName;

    public function fromTables(QueryBuilder $queryBuilder)
    {
        foreach ($this->fromTables as $fromTable) {
            $queryBuilder = $queryBuilder->from(
                $fromTable['entity'],
                $fromTable['as']
            );
        }
    }

    public function joinTables(QueryBuilder $queryBuilder)
    {
        foreach ($this->joinTables as $joinTable) {
            $queryBuilder = $queryBuilder->leftJoin(
                $joinTable['entity'],
                $joinTable['as'],
                \Doctrine\ORM\Query\Expr\Join::WITH,
                (isset($joinTable['condition']) && $joinTable['condition'] ? $joinTable['condition'] : null)
            );
        }
    }
}