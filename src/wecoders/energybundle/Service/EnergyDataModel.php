<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;

class EnergyDataModel
{
    const ENTITY = 'WecodersEnergyBundle:EnergyData';

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecordByData($array)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy($array);
    }

}