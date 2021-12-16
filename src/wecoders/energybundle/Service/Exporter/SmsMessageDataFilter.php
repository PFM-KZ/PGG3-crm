<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use TZiebura\ExporterBundle\Service\DataFilter\DataFilterInterface;

class SmsMessageDataFilter implements DataFilterInterface
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
        if ($request->query->get('name')) {
            $queryBuilder->setParameter('client_name', $request->query->get('name'));
        }

        if ($request->query->get('surname')) {
            $queryBuilder->setParameter('client_surname', $request->query->get('surname'));
        }

        if ($request->query->get('telephoneNr')) {
            $queryBuilder->setParameter('client_telephone_nr', $request->query->get('telephoneNr'));
        }

        if ($request->query->get('badgeId')) {
            $queryBuilder->setParameter('client_badge_id', $request->query->get('badgeId'));
        }

        if ($request->query->get('smsClientGroup')) {
            $queryBuilder->setParameter('smsClientGroup', $request->query->get('smsClientGroup'));
        }

        if ($request->query->get('status') && 'all' != $request->query->get('status')) {
            $queryBuilder->setParameter('status', $request->query->get('status'));
        }

        if ($request->query->get('createdAt')) {
            $day = new \DateTime($request->query->get('createdAt'));
            $nextDay = (clone $day)->modify('+1days');
            $queryBuilder->setParameter('dayCreatedAt', $day);
            $queryBuilder->setParameter('nextDayCreatedAt', $nextDay);
        }

        if ($request->query->get('sentAt')) {
            $day = new \DateTime($request->query->get('sentAt'));
            $nextDay = (clone $day)->modify('+1days');
            $queryBuilder->setParameter('daySentAt', $day);
            $queryBuilder->setParameter('nextDaySentAt', $nextDay);
        }
    }

    private function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [])
    {
        if ($request->query->get('name')) {
            $dqlAnd[] = ' c.name = :client_name';
        }
        if ($request->query->get('surname')) {
            $dqlAnd[] = ' c.surname = :client_surname';
        }
        if ($request->query->get('badgeId')) {
            $dqlAnd[] = 'c.badgeId = :client_badge_id';
        }

        if ($request->query->get('status') && 'all' != $request->query->get('status')) {
            $dqlAnd[] = 'sm.statusCode = :status';
        }

        if ($request->query->get('telephoneNr')) {
            $dqlAnd[] = '(c.telephoneNr = :client_telephone_nr OR c.contactTelephoneNr = :client_telephone_nr OR sm.number = :client_telephone_nr)';
        }

        if ($request->query->get('smsClientGroup')) {
            $dqlAnd[] = 'scg.id = :smsClientGroup';
        }

        if ($request->query->get('createdAt')) {
            $dqlAnd[] = 'sm.createdAt >= :dayCreatedAt AND sm.createdAt < :nextDayCreatedAt';
        }

        if ($request->query->get('sentAt')) {
            $dqlAnd[] = 'sm.sentAt >= :daySentAt AND sm.sentAt < :nextDaySentAt';
        }
    }
}