<?php

namespace GCRM\CRMBundle\Service;

use Complex\Exception;
use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ClientAndContractInterface;

class ClientModel
{
    const BADGE_ID_LENGTH = 12;

    const ENTITY = 'GCRMCRMBundle:Client';

    /** @var  EntityManager */
    private $em;

//    private $initialBadgeId = '005000000001';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }

    public function getRecords()
    {
        return $this->em->getRepository(self::ENTITY)->findAll();
    }

//    public function getClientsAndContracts($fromTables, $fromDepartment = null)
//    {
//        if (!is_array($fromTables)) {
//            return null;
//        }
//
//        $result = [];
//        foreach ($fromTables as $linkedTable => $contractTable) {
//            $qb = $this->em->createQueryBuilder();
//            $q = $qb->select(['a'])
//                ->from($linkedTable, 'a')
//                ->where('a.client IS NOT NULL')
//                ->andWhere('a.contract IS NOT NULL')
//            ;
//
//            if ($fromDepartment) {
//                $q->leftJoin(
//                    $contractTable,
//                    'b',
//                    'WITH',
//                    'a.contract = b.id'
//                );
//                $q->andWhere('b.statusDepartment = :statusDepartment')
//                    ->setParameters([
//                        'statusDepartment' => $fromDepartment
//                    ])
//                ;
//            }
//
//            $items = $q->getQuery()->getResult();
//            if ($items && count($items)) {
//                $result = array_merge($result, $items);
//            }
//        }
//
//        return $result;
//    }

//    public function filterClientsFromClientsAndContracts(&$clientsAndContracts)
//    {
//        $clients = [];
//        /** @var ClientAndContractInterface $clientsAndContract */
//        foreach ($clientsAndContracts as $clientsAndContract) {
//            if (!$clientsAndContract->getClient()) {
//                continue;
//            }
//            $clients[] = $clientsAndContract->getClient();
//        }
//
//        return $clients;
//    }

//    public function getLastGivenBadgeId()
//    {
//        $qb = $this->em->createQueryBuilder();
//        $q = $qb->select(['a.badgeId'])
//            ->from(self::ENTITY, 'a')
//            ->where('a.badgeId IS NOT NULL')
//            ->addOrderBy('a.badgeId', 'DESC')
//            ->getQuery()
//        ;
//
//        $result = $q->getScalarResult();
//        if ($result && count($result)) {
//            return $result[0]['badgeId'];
//        }
//
//        return null;
//    }

    public function getClientByBadgeId($badgeId)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from(self::ENTITY, 'a')
            ->leftJoin('GCRMCRMBundle:AccountNumberIdentifier', 'ani', 'WITH', 'a.accountNumberIdentifier = ani.id')
            ->where('ani.number = :badgeId')
            ->setParameters([
                'badgeId' => $badgeId
            ])
            ->getQuery()
        ;

        $result = $q->getResult();
        if ($result) {
            return $result[0];
        }
        return null;
    }

//    public function getBadgeIdFromAccountNumber($accountNumber)
//    {
//        $result = mb_substr($accountNumber, 14);
//
//        return $result;
//    }

//    public function generateBadgeId()
//    {
//        $lastGivenBadgeId = $this->getLastGivenBadgeId();
//        if ($lastGivenBadgeId) {
//            return $this->increaseBadgeIdNumber($lastGivenBadgeId);
//        }
//
//        return $this->initialBadgeId;
//    }

//    public function generateBankAccountNumber($bankStaticPartFromAccountNumber, $badgeId, $countryCode = 'PL')
//    {
//        $sumControl = $this->sumControl($countryCode . '00', $bankStaticPartFromAccountNumber, $badgeId);
//
//        return $sumControl . $bankStaticPartFromAccountNumber . $badgeId;
//    }

    public function isValidBankAccountNumber($bankAccountNumber, $countryCode = 'PL')
    {
        $bankAccountNumber = $countryCode . $bankAccountNumber;
        if (mb_strlen($bankAccountNumber) != 28) {
            throw new Exception();
        }

        $first4 = mb_substr($bankAccountNumber, 0, 4);
        $last24 = mb_substr($bankAccountNumber, 4);

        $bankAccountNumber = $this->replaceCharsToNumbers($last24 . $first4);
        $result = bcmod($bankAccountNumber, 97);

        if ($result != 1) {
            return false;
        }

        return true;
    }

//    private function increaseBadgeIdNumber($badgeId)
//    {
//        $badgeId++;
//
//        $badgeId = $this->prependZerosToMatchLength($badgeId, self::BADGE_ID_LENGTH);
//
//        return $badgeId;
//    }

    public function prependZerosToMatchLength($string, $length)
    {
        $prependZeroNumber = $length - strlen($string);
        for ($i = 0; $i < $prependZeroNumber; $i++) {
            $string = '0' . $string;
        }

        return $string;
    }

//    private function sumControl($first4, $static, $number)
//    {
//        $first4 = $this->replaceCharsToNumbers($first4);
//        $changed = $number . $first4;
//
//        $final = $static . $changed;
//        $result = 98 - bcmod($final, 97);
//        return mb_strlen($result) == 1 ? 0 . $result : $result;
//    }

    private function replaceCharsToNumbers($chars)
    {
        $chars = str_replace('P', 25, $chars);
        $chars = str_replace('L', 21, $chars);

        return $chars;
    }
}