<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Distributor;

class DistributorBranchModel
{
    const ENTITY = 'GCRMCRMBundle:DistributorBranch';

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }

    public function getRecordsByDistributor(Distributor $distributor)
    {
        return $this->em->getRepository(self::ENTITY)->findBy(['distributor' => $distributor]);
    }
}