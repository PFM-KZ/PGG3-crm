<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\AccountNumberIdentifier;

class AccountNumberIdentifierModel
{
    const NUMBER_LENGTH = 12;
    const ENTITY = 'GCRMCRMBundle:AccountNumberIdentifier';
    const DB_COLUMN_TITLE = 'number';

    private $initialNumber = '005000000001';

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function add($number)
    {
        $accountNumberIdentifier = new AccountNumberIdentifier();
        $accountNumberIdentifier->setNumber($number);

        $this->em->persist($accountNumberIdentifier);
        $this->em->flush($accountNumberIdentifier);

        return $accountNumberIdentifier;
    }

    public function isUsed($number)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy([self::DB_COLUMN_TITLE => $number]) ? true : false;
    }

    public function generateNumber()
    {
        $lastAddedNumber = $this->fetchLastNumber();
        if ($lastAddedNumber) {
            return $this->increaseNumber($lastAddedNumber);
        }

        return $this->initialNumber;
    }

    private function fetchLastNumber()
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a.' . self::DB_COLUMN_TITLE])
            ->from(self::ENTITY, 'a')
            ->addOrderBy('a.' . self::DB_COLUMN_TITLE, 'DESC')
            ->setMaxResults(1)
            ->getQuery()
        ;

        /** @var AccountNumberIdentifier $result */
        $result = $q->getResult();

        if ($result) {
            return $result[0][self::DB_COLUMN_TITLE];
        }

        return null;
    }

    private function increaseNumber($number)
    {
        $number++;
        $number = $this->prependZerosToMatchLength($number, self::NUMBER_LENGTH);

        return $number;
    }

    private function prependZerosToMatchLength($string, $length)
    {
        $prependZeroNumber = $length - strlen($string);
        for ($i = 0; $i < $prependZeroNumber; $i++) {
            $string = '0' . $string;
        }

        return $string;
    }
}