<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use TZiebura\ExporterBundle\Service\DataFilter\DataFilterInterface;

class ClientOverduePaymentsDataFilter implements DataFilterInterface
{
    public function addCriteria(Request $request, QueryBuilder $queryBuilder)
    {
        $queryBuilder->where(
            'i.isPaid = false',
            'i.dateOfPayment < :today'
        );

        $queryBuilder->setParameter('today', (new \DateTime())->setTime(0,0));
    }
}