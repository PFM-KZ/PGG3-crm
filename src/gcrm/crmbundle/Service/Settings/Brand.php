<?php

namespace GCRM\CRMBundle\Service\Settings;

use Doctrine\ORM\EntityManager;

class Brand
{
    const ENTITY = 'GCRMCRMBundle:Settings\Brand';

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecord($name)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['name' => $name]);
    }

}