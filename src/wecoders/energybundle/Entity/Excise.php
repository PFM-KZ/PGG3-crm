<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Service\OsdModel;

/**
 * Excise
 *
 * @ORM\Table(name="excise")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\ExciseRepository")
 */
class Excise
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
     * @var \DateTime
     *
     * @ORM\Column(name="from_date", type="date", nullable=true)
     */
    private $fromDate;

    /**
     * @var string
     *
     * @ORM\Column(name="excise_value", type="string", length=255)
     */
    protected $exciseValue;

    /**
     * @return \DateTime
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * @param \DateTime $fromDate
     */
    public function setFromDate($fromDate)
    {
        $this->fromDate = $fromDate;
    }

    /**
     * @return string
     */
    public function getExciseValue()
    {
        return $this->exciseValue;
    }

    /**
     * @param string $exciseValue
     */
    public function setExciseValue($exciseValue)
    {
        $this->exciseValue = $exciseValue;
    }
}
