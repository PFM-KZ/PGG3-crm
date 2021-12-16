<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;

class PackageToGenerateModel
{
    const STATUS_WAITING_TO_PROCESS = 1;
    const STATUS_IN_PROCESS = 2;
    const STATUS_WAITING_TO_GENERATE = 3;
    const STATUS_GENERATE = 4;
    const STATUS_COMPLETE = 5;
    const STATUS_GENERATE_ERROR = 101;
    const STATUS_PROCESS_ERROR = 201;
    const ENTITY = 'WecodersEnergyBundle:PackageToGenerate';

    private $em;

    public function getOptionArray()
    {
        return [
            self::STATUS_WAITING_TO_PROCESS => 'czeka do procesu',
            self::STATUS_IN_PROCESS => 'w toku przetwarzania',
            self::STATUS_WAITING_TO_GENERATE => 'czeka do generowania',
            self::STATUS_GENERATE => 'trwa generowanie dokumentów',
            self::STATUS_COMPLETE => 'zakończono',
            self::STATUS_GENERATE_ERROR => 'błąd generowania',
            self::STATUS_PROCESS_ERROR => 'błąd procesowania',
        ];
    }

    public function getOptionValue($option)
    {
        $options = $this->getOptionArray();
        if ($options) {
            foreach ($options as $key => $value) {
                if ($key == $option) {
                    return $value;
                }
            }
        }

        return null;
    }

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getSingleRecordByStatus($status)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['status' => $status]);
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }
}