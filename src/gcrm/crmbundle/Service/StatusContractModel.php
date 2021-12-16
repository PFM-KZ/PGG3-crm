<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\StatusContract;
use GCRM\CRMBundle\Entity\StatusContractAndSpecialAction;
use GCRM\CRMBundle\Entity\StatusContractAuthorization;

class StatusContractModel
{
    const ENTITY = 'GCRMCRMBundle:StatusContract';

    const AUTHORIZATION_POSITIVE_CODE = 1;
    const AUTHORIZATION_NEGATIVE_CODE = 2;

    const SPECIAL_ACTION_SET_THIS_STATUS_AFTER_RETURN = 1;
    const SPECIAL_ACTION_CHOOSE_TO_PAYMENT_REQUEST = 2;
    const SPECIAL_ACTION_CHOOSE_TO_PROFORMA = 3;
    const SPECIAL_ACTION_ACTIVE_CLIENT = 4;
    const SPECIAL_ACTION_CONTRACT_END = 5;
    const SPECIAL_ACTION_CHOOSE_TO_DEBIT_NOTE_RESIGNATION_BEFORE_PROCESS = 6;
    const SPECIAL_ACTION_CHOOSE_TO_DEBIT_NOTE_RESIGNATION_AFTER_PROCESS = 7;

    private $em;

    public static function getSpecialActionOptionArray()
    {
        return [
            self::SPECIAL_ACTION_SET_THIS_STATUS_AFTER_RETURN => 'Ustaw ten status po zwrocie',
            self::SPECIAL_ACTION_CHOOSE_TO_PAYMENT_REQUEST => 'Uwzględnij ten status przy wystawianiu wezwań do zapłaty',
            self::SPECIAL_ACTION_CHOOSE_TO_PROFORMA => 'Uwzględnij ten status przy wystawianiu proform',
            self::SPECIAL_ACTION_CHOOSE_TO_DEBIT_NOTE_RESIGNATION_BEFORE_PROCESS => 'Do not obciążeniowych - przed realizacją',
            self::SPECIAL_ACTION_CHOOSE_TO_DEBIT_NOTE_RESIGNATION_AFTER_PROCESS => 'Do not obciążeniowych - realizowane',
            self::SPECIAL_ACTION_ACTIVE_CLIENT => 'Jest aktywnym klientem',
            self::SPECIAL_ACTION_CONTRACT_END => 'Zakończenie umowy',
        ];
    }

    public static function getSpecialActionOptionByValue($value)
    {
        $options = self::getSpecialActionOptionArray();
        foreach ($options as $key => $option) {
            if ($key == $value) {
                return $option;
            }
        }

        return null;
    }

    public static function getOptionArray()
    {
        return [
            self::AUTHORIZATION_POSITIVE_CODE => 'Autoryzacja pozytywna',
            self::AUTHORIZATION_NEGATIVE_CODE => 'Autoryzacja negatywna',
        ];
    }

    public static function getOptionByValue($value)
    {
        $options = self::getOptionArray();
        foreach ($options as $key => $option) {
            if ($key == $value) {
                return $option;
            }
        }

        return null;
    }

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getAllStatusContractFromAuthorization()
    {
        $data = $this->em->getRepository('GCRMCRMBundle:StatusContractAuthorization')->findAll();
        $result = [];
        if ($data) {
            /** @var StatusContractAuthorization $statusContractAuthorization */
            foreach ($data as $statusContractAuthorization) {
                $statusContract = $statusContractAuthorization->getStatusContract();
                if (!$statusContract) {
                    continue;
                }
                $result[] = $statusContract;
            }
        }

        return $result;
    }

    public function getStatusContractsBySpecialActionOption($option)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from(self::ENTITY, 'a')
            ->leftJoin('GCRMCRMBundle:StatusContractAndSpecialAction', 'b', 'WITH', 'a.id = b.statusContract')
            ->where('b.option = :option')
            ->setParameter('option', $option)
        ;

        if (is_array($option)) {
            $q->where('b.option IN (:option)');
        } else {
            $q->where('b.option = :option');
        }

        return $q->getQuery()->getResult();
    }

    public function getStatusContractsIdsBySpecialActionOption($option)
    {
        $ids = [];
        $statuses = $this->getStatusContractsBySpecialActionOption($option);
        if ($statuses) {
            /** @var StatusContract $status */
            foreach ($statuses as $status) {
                $ids[] = $status->getId();
            }
        }

        return $ids;
    }

    public function containSpecialActionOption(StatusContract $statusContract, $option)
    {
        /** @var StatusContractAndSpecialAction $specialAction */
        foreach ($statusContract->getSpecialActions() as $specialAction) {
            if ($specialAction->getOption() == $option) {
                return true;
            }
        }

        return false;
    }
}