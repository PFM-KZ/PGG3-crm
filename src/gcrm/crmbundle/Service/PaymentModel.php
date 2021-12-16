<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Payment;
use Wecoders\EnergyBundle\Entity\PaymentRequest;

class PaymentModel
{
    const ENTITY = 'GCRMCRMBundle:Payment';

    /** @var  EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecords()
    {
        return $this->em->getRepository(self::ENTITY)->findAll();
    }

    public function getRecordsByDates($from = null, $to = null)
    {
        $parameters = [];

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from(self::ENTITY, 'a')
            ->orderBy('a.date', 'ASC')
            ;

        if ($from && $from instanceof \DateTime) {
            $from->setTime(0, 0);
            $q->andWhere('a.date >= :from');
            $parameters['from'] = $from;
        }

        if ($to && $to instanceof \DateTime) {
            $to->setTime(0, 0);
            $to->modify('+ 1 day');
            $q->andWhere('a.date < :to');
            $parameters['to'] = $to;
        }

        return $q->setParameters($parameters)->getQuery()->getResult();
    }

    public function getPaymentsByNumber($badgeId, $orderPosition = 'ASC')
    {
        return $this->em->getRepository(self::ENTITY)->findBy([
            'badgeId' => $badgeId
        ], ['createdAt' => $orderPosition]);
    }

    public function getPaymentsByNumberFromDate($badgeId, $dateFrom = null, $orderPosition = 'ASC')
    {
        $parameters = [
            'badgeId' => $badgeId,
        ];

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from(self::ENTITY, 'a')
            ->orderBy('a.createdAt', $orderPosition)
            ->where('a.badgeId = :badgeId')
        ;

        if ($dateFrom) {
            $q->andWhere('a.date >= :dateFrom');
            $parameters['dateFrom'] = $dateFrom;
        }

        return $q->setParameters($parameters)->getQuery()->getResult();
    }

    public function getPaymentsSummaryValueByNumberFromDate($badgeId, $dateFrom = null)
    {
        $result = 0;

        $payments = $this->getPaymentsByNumberFromDate($badgeId, $dateFrom);
        if ($payments) {
            /** @var Payment $payment */
            foreach ($payments as $payment) {
                $result += $payment->getValue();
            }
        }

        return $result;
    }

    public function calculateSummaryFromPayments($payments)
    {
        $result = 0;

        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $result += str_replace(',', '', $payment->getValue());
        }

        return $result;
    }

}