<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Wecoders\EnergyBundle\Entity\MergeInvoicesPackage;

class MergeInvoicesPackageRecordModel
{
    const ENTITY = 'WecodersEnergyBundle:MergeInvoicesPackageRecord';

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }

    public function getRecordsByPackage(MergeInvoicesPackage $mergeInvoicesPackage)
    {
        return $this->em->getRepository(self::ENTITY)->findBy(['package' => $mergeInvoicesPackage]);
    }
}