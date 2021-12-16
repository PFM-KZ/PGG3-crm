<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\Excise;

class ExciseModel
{
    const ENTITY = 'WecodersEnergyBundle:Excise';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getCurrentExciseValue()
    {
        $now = new \DateTime();

        return $this->getExciseValueByDate($now);
    }

    public function getExciseValueByDate(\DateTime $dateTime)
    {
        $records = $this->em->getRepository(self::ENTITY)->findBy([], ['fromDate' => 'DESC']);
        if (!$records) {
            return null;
        }

        $dateTime->setTime(0, 0);

        /** @var Excise $record */
        foreach ($records as $record) {
            /** @var \DateTime $fromDate */
            $recordFromDate = $record->getFromDate();
            $recordFromDate->setTime(0, 0);

            if ($record->getFromDate() <= $dateTime) {
                return $record->getExciseValue();
            }
        }

        return null;
    }
}