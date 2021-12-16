<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tariff
 *
 * @ORM\Table(name="tariff_energy")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\TariffRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Tariff
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="energy_type", type="integer", nullable=true)
     */
    protected $energyType;

    /**
     * @return string
     */
    public function getEnergyType()
    {
        return $this->energyType;
    }

    /**
     * @param string $energyType
     */
    public function setEnergyType($energyType)
    {
        $this->energyType = $energyType;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="invoicing_period_in_months", type="integer")
     */
    protected $invoicingPeriodInMonths;

    /**
     * @return string
     */
    public function getInvoicingPeriodInMonths()
    {
        return $this->invoicingPeriodInMonths;
    }

    /**
     * @param string $invoicingPeriodInMonths
     */
    public function setInvoicingPeriodInMonths($invoicingPeriodInMonths)
    {
        $this->invoicingPeriodInMonths = $invoicingPeriodInMonths;
    }

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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    public function __toString()
    {
        return $this->title;
    }
}

