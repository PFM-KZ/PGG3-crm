<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Client;

class ClientProcedureTPModel
{
    const ENTITY = 'GCRMCRMBundle:ClientProcedureTP';

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getRecordByClient(Client $client)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['client' => $client]);
    }

}