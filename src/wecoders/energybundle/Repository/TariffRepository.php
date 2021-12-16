<?php

namespace Wecoders\EnergyBundle\Repository;

/**
 * TariffRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TariffRepository extends \Doctrine\ORM\EntityRepository
{
    public function filterTariffByEnergyType($energyType)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.energyType = :energyType')
            ->setParameter('energyType', $energyType);
    }
}
