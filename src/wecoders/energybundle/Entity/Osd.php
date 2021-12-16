<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Service\OsdModel;

/**
 * Osd
 *
 * @ORM\Table(name="osd")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\OsdRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Osd
{
    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\OsdAndOsdData", mappedBy="osd", cascade={"persist","remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"activeFrom" = "ASC"})
     */
    private $osdAndOsdDatas;

    public function addOsdAndOsdData(OsdAndOsdData $osdAndOsdData)
    {
        $this->osdAndOsdDatas[] = $osdAndOsdData;
        $osdAndOsdData->setOsd($this);

        return $this;
    }

    public function removeOsdAndOsdData(OsdAndOsdData $osdAndOsdData)
    {
        $this->osdAndOsdDatas->removeElement($osdAndOsdData);
    }

    public function getOsdAndOsdDatas()
    {
        return $this->osdAndOsdDatas;
    }

    public function setOsdAndOsdDatas($osdAndOsdDatas)
    {
        $this->osdAndOsdDatas = $osdAndOsdDatas;
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

    /**
     * @var string
     *
     * @ORM\Column(name="option", type="integer", unique=true)
     */
    protected $option;

    /**
     * @return string
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * @param string $option
     */
    public function setOption($option)
    {
        $this->option = $option;
    }

    public function __toString()
    {
        return OsdModel::getOptionByValue($this->option);
    }
}

