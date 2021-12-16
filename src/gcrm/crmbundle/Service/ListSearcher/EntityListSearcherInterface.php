<?php

namespace GCRM\CRMBundle\Service\ListSearcher;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;

interface EntityListSearcherInterface
{
    public function getEntity();
    public function getTwigTemplate();
    public function joinTables(QueryBuilder $queryBuilder);
    public function addParameters(QueryBuilder $queryBuilder, Request $request);
    public function addFields(FormBuilder $builder, $options, EntityManager $em);
    public function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [], $statusDepartments);
}