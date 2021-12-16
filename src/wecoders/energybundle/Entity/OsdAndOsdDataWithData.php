<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OsdAndOsdDataWithData
 *
 * @ORM\Table(name="osd_and_osd_data_with_data")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\OsdRepository")
 * @ORM\HasLifecycleCallbacks
 */
class OsdAndOsdDataWithData
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\OsdAndOsdData", inversedBy="osdAndOsdDataWithDatas")
     * @ORM\JoinColumn(name="osd_and_osd_data_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $osdAndOsdData;

    /**
     * @return mixed
     */
    public function getOsdAndOsdData()
    {
        return $this->osdAndOsdData;
    }

    /**
     * @param mixed $osdAndOsdData
     */
    public function setOsdAndOsdData($osdAndOsdData)
    {
        $this->osdAndOsdData = $osdAndOsdData;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="tariff", type="string", length=255)
     */
    protected $tariff;

    /**
     * @return string
     */
    public function getTariff()
    {
        return $this->tariff;
    }

    /**
     * @param string $tariff
     */
    public function setTariff($tariff)
    {
        $this->tariff = $tariff;
    }

    /**
     * @var float
     *
     * @ORM\Column(name="fee_constant", type="string", length=255, nullable=true)
     */
    private $feeConstant;

    /**
     * @var float
     *
     * @ORM\Column(name="fee_variable", type="string", length=255, nullable=true)
     */
    private $feeVariable;

    /**
     * @return float
     */
    public function getFeeConstant()
    {
        return $this->feeConstant;
    }

    /**
     * @param float $feeConstant
     */
    public function setFeeConstant($feeConstant)
    {
        $this->feeConstant = $feeConstant;
    }

    /**
     * @return float
     */
    public function getFeeVariable()
    {
        return $this->feeVariable;
    }

    /**
     * @param float $feeVariable
     */
    public function setFeeVariable($feeVariable)
    {
        $this->feeVariable = $feeVariable;
    }

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

    public function __toString()
    {
        return $this->tariff;
    }
}

