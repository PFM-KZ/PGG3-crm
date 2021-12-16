<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;

class BrandModel
{
    const ENTITY = 'WecodersEnergyBundle:Brand';

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecords()
    {
        return $this->em->getRepository(self::ENTITY)->findAll();
    }

}