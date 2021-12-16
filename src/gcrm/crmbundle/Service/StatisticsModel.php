<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Contract;

class StatisticsModel
{
    const DATE_FILTER_INVOICES_GROSS_ID = 1;
    const DATE_FILTER_INVOICES_GROSS_TITLE = 'Faktury brutto';

    const DATE_FILTER_INVOICES_NET_ID = 2;
    const DATE_FILTER_INVOICES_NET_TITLE = 'Faktury netto';

    const DATE_FILTER_ADDED_CLIENTS_ID = 100;
    const DATE_FILTER_ADDED_CLIENTS_TITLE = 'Wprowadzeni klienci';

    const DATE_FILTER_ADDED_CONTRACTS_ID = 200;
    const DATE_FILTER_ADDED_CONTRACTS_TITLE = 'Wprowadzone umowy';


    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getOptionsArray()
    {
        return [
            self::DATE_FILTER_INVOICES_GROSS_TITLE => self::DATE_FILTER_INVOICES_GROSS_ID,
            self::DATE_FILTER_INVOICES_NET_TITLE => self::DATE_FILTER_INVOICES_NET_ID,
            self::DATE_FILTER_ADDED_CLIENTS_TITLE => self::DATE_FILTER_ADDED_CLIENTS_ID,
            self::DATE_FILTER_ADDED_CONTRACTS_TITLE => self::DATE_FILTER_ADDED_CONTRACTS_ID,
        ];
    }

    public function getOptionsArrayByIds(array $ids)
    {
        $optionArray = $this->getOptionsArray();

        $result = [];
        foreach ($optionArray as $key => $value) {
            if (in_array($value, $ids)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function getAllowedOptionIds()
    {
        $result = [];
        $optionArray = $this->getOptionsArray();
        foreach ($optionArray as $value) {
            $result[] = $value;
        }

        return $result;
    }

    public function getDateFromToValue(\DateTime $dateFrom, \DateTime $dateTo, $option)
    {
        $allowedOptionIds = $this->getAllowedOptionIds();

        if (!in_array($option, $allowedOptionIds)) {
            return null;
        }

        $result = null;
        if ($option == 1) {
            $result = $this->getInvoiceGrossValueByDate($dateFrom, $dateTo);
        } elseif ($option == 2) {
            $result = $this->getInvoiceNetValueByDate($dateFrom, $dateTo);
        } elseif ($option == 100) {
            $result = $this->getAddedClientsCountByDate($dateFrom, $dateTo);
        } elseif ($option == 200) {
            $result = $this->getAddedContractsCountByDate($dateFrom, $dateTo);
        }

        return $result;
    }

    public function getInvoiceGrossValueByDate(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['SUM(a.summaryGrossValue)'])
            ->from('GCRMCRMBundle:Invoices', 'a')
            ->where('a.createdAt >= :dateFrom')
            ->andWhere('a.createdAt <= :dateTo')
            ->setParameters([
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ])
            ->getQuery()
        ;

        return $q->getSingleScalarResult() ? number_format($q->getSingleScalarResult(), 2) : 0;
    }

    public function getInvoiceNetValueByDate(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['SUM(a.summaryNetValue)'])
            ->from('GCRMCRMBundle:Invoices', 'a')
            ->where('a.createdAt >= :dateFrom')
            ->andWhere('a.createdAt <= :dateTo')
            ->setParameters([
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ])
            ->getQuery()
        ;

        return $q->getSingleScalarResult() ? number_format($q->getSingleScalarResult(), 2) : 0;
    }

    public function getAddedClientsCountByDate(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['count(a.id)'])
            ->from('GCRMCRMBundle:Client', 'a')
            ->where('a.createdAt >= :dateFrom')
            ->andWhere('a.createdAt <= :dateTo')
            ->setParameters([
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ])
            ->getQuery()
        ;

        return $q->getSingleScalarResult();
    }

    public function getAddedContractsCountByDate(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['count(a.id)'])
            ->from('GCRMCRMBundle:Contract', 'a')
            ->where('a.createdAt >= :dateFrom')
            ->andWhere('a.createdAt <= :dateTo')
            ->setParameters([
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo
            ])
            ->getQuery()
        ;

        return $q->getSingleScalarResult();
    }

    public function getInvoicesCount()
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['count(invoice.id)'])
            ->from('GCRMCRMBundle:Invoices', 'invoice')
            ->getQuery()
        ;

        return $q->getSingleScalarResult();
    }

    public function getDailyInvoicesCount()
    {
        $tempNow = new \DateTime('now');
        $dayStartDate = $tempNow->setTime(0, 0);

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['count(invoice.id)'])
            ->from('GCRMCRMBundle:Invoices', 'invoice')
            ->where('invoice.createdAt >= :dayStartDate')
            ->andWhere('invoice.createdAt <= :now')
            ->setParameters([
                'dayStartDate' => $dayStartDate,
                'now' => new \DateTime('now')
            ])
            ->getQuery()
        ;

        return $q->getSingleScalarResult();
    }

    public function getClientsCount()
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['count(client.id)'])
            ->from('GCRMCRMBundle:Client', 'client')
            ->getQuery()
        ;

        return $q->getSingleScalarResult();
    }

    public function getDailyClientsCount()
    {
        $tempNow = new \DateTime('now');
        $dayStartDate = $tempNow->setTime(0, 0);

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['count(client.id)'])
            ->from('GCRMCRMBundle:Client', 'client')
            ->where('client.createdAt >= :dayStartDate')
            ->andWhere('client.createdAt <= :now')
            ->setParameters([
                'dayStartDate' => $dayStartDate,
                'now' => new \DateTime('now')
            ])
            ->getQuery()
        ;

        return $q->getSingleScalarResult();
    }

    public function getContractsCount()
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['count(contract.id)'])
            ->from('GCRMCRMBundle:Contract', 'contract')
            ->getQuery()
        ;

        return $q->getSingleScalarResult();
    }

    public function getDailyContractsCount()
    {
        $tempNow = new \DateTime('now');
        $dayStartDate = $tempNow->setTime(0, 0);

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['count(contract.id)'])
            ->from('GCRMCRMBundle:Contract', 'contract')
            ->where('contract.createdAt >= :dayStartDate')
            ->andWhere('contract.createdAt <= :now')
            ->setParameters([
                'dayStartDate' => $dayStartDate,
                'now' => new \DateTime('now')
            ])
            ->getQuery()
        ;

        return $q->getSingleScalarResult();
    }

    public function getSalesRepresentativesCount()
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['count(user.id)'])
            ->from('GCRMCRMBundle:User', 'user')
            ->where('user.isSalesRepresentative = :isSalesRepresentative')
            ->andWhere('user.enabled = :enabled')
            ->setParameters([
                'isSalesRepresentative' => true,
                'enabled' => true
            ])
            ->getQuery()
        ;

        return $q->getSingleScalarResult();
    }

    public function chartDataCurrentMonth()
    {
        $daysInMonth = date('t');

        $result = [
            'dates' => [],
            'values' => [
                'contracts' => [],
            ]
        ];

        $firstDayOfMonth = new \DateTime('first day of this month');
        $firstDayOfMonth->setTime(0,0,0);
        $temp = clone $firstDayOfMonth;
        $firstDayNextMonth = $temp->modify('+1 month');

        // Get contracts
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('GCRMCRMBundle:Contract', 'a')
            ->where('a.createdAt >= :firstDayOfMonth')
            ->andWhere('a.createdAt < :firstDayNextMonth')
            ->setParameters([
                'firstDayOfMonth' => $firstDayOfMonth,
                'firstDayNextMonth' => $firstDayNextMonth
            ])
            ->getQuery()
        ;
        $contracts = $q->getResult();

        // Get clients
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('GCRMCRMBundle:Client', 'a')
            ->where('a.createdAt >= :firstDayOfMonth')
            ->andWhere('a.createdAt < :firstDayNextMonth')
            ->setParameters([
                'firstDayOfMonth' => $firstDayOfMonth,
                'firstDayNextMonth' => $firstDayNextMonth
            ])
            ->getQuery()
        ;
        $clients = $q->getResult();

        $contractsGroupedByDates = [];
        /** @var Contract $contract */
        foreach ($contracts as $contract) {
            $date = $contract->getCreatedAt();
            $date = $date->format('Y-m-d');

            if (!key_exists($date, $contractsGroupedByDates)) {
                $contractsGroupedByDates[$date] = 0;
            }

            $contractsGroupedByDates[$date]++;
        }

        $clientsGroupedByDates = [];
        /** @var Client $client */
        foreach ($clients as $client) {
            $date = $client->getCreatedAt();
            $date = $date->format('Y-m-d');

            if (!key_exists($date, $clientsGroupedByDates)) {
                $clientsGroupedByDates[$date] = 0;
            }

            $clientsGroupedByDates[$date]++;
        }

        $year = $firstDayOfMonth->format('Y');
        $month = $firstDayOfMonth->format('m');

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = new \DateTime();
            $date = $date->setDate($year, $month, $i);
            $date = $date->format('Y-m-d');
            $result['dates'][] = $date;
            $result['values']['contracts'][] = key_exists($date, $contractsGroupedByDates) ? $contractsGroupedByDates[$date] : 0;
            $result['values']['clients'][] = key_exists($date, $clientsGroupedByDates) ? $clientsGroupedByDates[$date] : 0;
        }

        return $result;
    }

    public function chartDataNumberOfDaysBack($dataFetch, $daysBack = 30)
    {
        $result = [
            'dates' => [],
            'values' => []
        ];

        $dateTo = new \DateTime();
        $temp = clone $dateTo;
        $dateFrom = $temp->modify('-' . $daysBack . ' days');

        $tempDateFrom = clone $dateFrom;

        for ($i = 1; $i <= $daysBack; $i++) {
            $date = $tempDateFrom->modify('+1 day');
            $date = $date->format('Y-m-d');
            $result['dates'][] = $date;

        }
        foreach ($dataFetch as $key => $value) {
            $values = $this->entityRecordsGroupedByDates(key($value['entity']), 30);
            $result['values'][] = [
                'title' => $value['title'],
                'color' => $value['color'],
                'values' => $values,
                'valuesMergedToString' => implode(',', $values),
            ];
        }

        return $result;
    }

    public function entityRecordsGroupedByDates($entity, $daysBack = 30)
    {
        $dateTo = new \DateTime();
        $temp = clone $dateTo;
        $dateFrom = $temp->modify('-' . $daysBack . ' days');

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from($entity, 'a')
            ->where('a.createdAt >= :dateFrom')
            ->andWhere('a.createdAt < :dateTo')
            ->setParameters([
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ])
            ->getQuery()
        ;
        $items = $q->getResult();

        $recordsGroupedByDates = [];
        /** @var Contract $contract */
        foreach ($items as $item) {
            $date = $item->getCreatedAt();
            $date = $date->format('Y-m-d');

            if (!key_exists($date, $recordsGroupedByDates)) {
                $recordsGroupedByDates[$date] = 0;
            }

            $recordsGroupedByDates[$date]++;
        }

        $tempDateFrom = clone $dateFrom;

        $result = [];
        for ($i = 1; $i <= $daysBack; $i++) {
            $date = $tempDateFrom->modify('+1 day');
            $date = $date->format('Y-m-d');
            $result[] = key_exists($date, $recordsGroupedByDates) ? $recordsGroupedByDates[$date] : 0;
        }

        return $result;
    }
}