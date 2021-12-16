<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TZiebura\ExporterBundle\Service\DataFilter\DataFilterInterface;

class CorrespondenceDataFilter implements DataFilterInterface
{
    /** @var ContainerInterface $container */
    private $container;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function addCriteria(Request $request, QueryBuilder $queryBuilder)
    {
        $this->addParameters($queryBuilder, $request);

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

    public function addParameters(QueryBuilder $queryBuilder, Request $request)
    {
        if ($request->query->get('status')) {
            $queryBuilder->setParameter('eIsActive', $request->query->get('status'));
        }
        if ($request->query->get('dispatchDate')) {
            $queryBuilder->setParameter('eDispatchDate', new \DateTime($request->query->get('dispatchDate')));
        }
        if ($request->query->get('replyDeadline')) {
            $queryBuilder->setParameter('eReplyDeadline', new \DateTime($request->query->get('replyDeadline')));
        }
        if ($request->query->get('ctype')) {
            $queryBuilder->setParameter('ctype', $request->query->get('ctype'));
        }
        if ($request->query->get('sender')) {
            $queryBuilder->setParameter('sender', '%' . $request->query->get('sender') . '%');
        }
        if($request->query->get('type')) {
            $queryBuilder->setParameter('threadType', $request->query->get('type'));
        }
        if ($request->query->has('lsBrand') && $request->query->get('lsBrand')) {
            $queryBuilder->setParameter('cBrand', $request->query->get('lsBrand'));
        }
        if ($request->query->get('lsPesel')) {
            $queryBuilder->setParameter('entityPesel', '%' . $request->query->get('lsPesel') . '%');
        }
        if ($request->query->get('lsTelephoneNr')) {
            $queryBuilder->setParameter('entityTelephoneNr', '%' . $request->query->get('lsTelephoneNr') . '%');
        }
        if ($request->query->get('lsName')) {
            $queryBuilder->setParameter('entityName', '%' . $request->query->get('lsName') . '%');
        }
        if ($request->query->get('lsSurname')) {
            $queryBuilder->setParameter('entitySurname', '%' . $request->query->get('lsSurname') . '%');
        }
        if ($request->query->get('lsNip')) {
            $queryBuilder->setParameter('entityNip', '%' . $request->query->get('lsNip') . '%');
        }
        if ($request->query->get('lsBadgeId')) {
            $queryBuilder->setParameter('entityBadgeId', '%' . $request->query->get('lsBadgeId') . '%');
        }
        if ($request->query->get('lsContractNumber')) {
            $queryBuilder->setParameter('cContractNumber', $request->query->get('lsContractNumber'));
        }
        if ($request->query->get('lsContractType')) {
            $queryBuilder->setParameter('cContractType', $request->query->get('lsContractType'));
        }
        if ($request->query->get('ppCode')) {
            $queryBuilder->setParameter('ppCode', '%' . $request->query->get('ppCode') . '%' );
        }
    }

    public function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [], $statusDepartments = array())
    {
        if ($request->query->get('lsHideNotActual')) {
            $tempOr = [
                ' (cgas.isResignation != 1) ',
                ' (cenergy.isResignation != 1) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';

            $tempOr = [
                ' (cgas.isBrokenContract != 1) ',
                ' (cenergy.isBrokenContract != 1) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('status')) {
            $dqlAnd[] = '(t.isActive = :eIsActive)';
        }

        if ($request->query->get('dispatchDate')) {
            $dqlAnd[] = '(t.dispatchDate = :eDispatchDate)';
        }

        if ($request->query->get('replyDeadline')) {
            $dqlAnd[] = '(t.mainReplyDeadline <= :eReplyDeadline AND t.mainReplyDeadline IS NOT NULL)';
        }

        if ($request->query->get('ctype')) {
            $dqlAnd[] = '(t.type = :ctype)';
        }

        if ($request->query->get('sender')) {
            $dqlAnd[] = '(t.sender = :sender)';
        }

        if ($request->query->get('type')) {
            $dqlAnd[] = '(tt.id = :threadType)';
        }

        if ($request->query->get('lsBrand')) {
            $tempOr = [
                ' (cgas.brand = :cBrand) ',
                ' (cenergy.brand= :cBrand) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsContractNumber')) {
            $tempOr = [
                ' (cgas.contractNumber LIKE :cContractNumber) ',
                ' (cenergy.contractNumber LIKE :cContractNumber) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsContractType')) {
            $tempOr = [
                ' (cgas.type = :cContractType) ',
                ' (cenergy.type = :cContractType) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' (client.pesel LIKE :entityPesel OR cgas.secondPersonPesel LIKE :entityPesel OR cenergy.secondPersonPesel LIKE :entityPesel)';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' client.telephoneNr LIKE :entityTelephoneNr';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' (client.name LIKE :entityName OR cgas.secondPersonName LIKE :entityName OR cenergy.secondPersonName LIKE :entityName)';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' (client.surname LIKE :entitySurname OR cgas.secondPersonSurname LIKE :entitySurname OR cenergy.secondPersonSurname LIKE :entitySurname)';
        }
        if ($request->query->get('lsNip')) {
            $dqlAnd[] = ' client.nip LIKE :entityNip';
        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' client.badgeId LIKE :entityBadgeId';
        }
        if ($request->query->get('ppCode')) {
            $dqlAnd[] = ' (cgas.ppCode LIKE :ppCode OR cenergy.ppCode LIKE :ppCode )';
        }
    }
}