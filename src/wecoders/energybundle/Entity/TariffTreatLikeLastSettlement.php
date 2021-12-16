<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tariff
 *
 * @ORM\Table(name="tariff_treat_like_last_settlement")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\TariffTreatLikeLastSettlementRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TariffTreatLikeLastSettlement
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\Tariff")
     * @ORM\JoinColumn(name="tariff_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $tariff;

    /**
     * @return mixed
     */
    public function getTariff()
    {
        return $this->tariff;
    }

    /**
     * @param mixed $tariff
     */
    public function setTariff($tariff)
    {
        $this->tariff = $tariff;
    }

}

