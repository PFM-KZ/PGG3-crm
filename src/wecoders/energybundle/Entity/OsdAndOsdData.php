<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Osd
 *
 * @ORM\Table(name="osd_and_osd_data")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\OsdRepository")
 * @ORM\HasLifecycleCallbacks
 */
class OsdAndOsdData
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\Osd", inversedBy="osdAndOsdData")
     * @ORM\JoinColumn(name="osd_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $osd;

    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\OsdAndOsdDataWithData", mappedBy="osdAndOsdData", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $osdAndOsdDataWithDatas;

    public function addOsdAndOsdDataWithData(OsdAndOsdDataWithData $osdAndOsdDataWithData)
    {
        $this->osdAndOsdDataWithDatas[] = $osdAndOsdDataWithData;
        $osdAndOsdDataWithData->setOsdAndOsdData($this);

        return $this;
    }

    public function removeOsdAndOsdDataWithData(OsdAndOsdDataWithData $osdAndOsdDataWithData)
    {
        $this->osdAndOsdDataWithDatas->removeElement($osdAndOsdDataWithData);
    }

    public function getOsdAndOsdDataWithDatas()
    {
        return $this->osdAndOsdDataWithDatas;
    }

    public function setOsdAndOsdDataWithDatas($osdAndOsdDataWithDatas)
    {
        $this->osdAndOsdDataWithDatas = $osdAndOsdDataWithDatas;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="active_from", type="date")
     */
    protected $activeFrom;

    /**
     * @return \DateTime
     */
    public function getActiveFrom()
    {
        return $this->activeFrom;
    }

    /**
     * @param \DateTime $activeFrom
     */
    public function setActiveFrom($activeFrom)
    {
        $this->activeFrom = $activeFrom;
    }

    /**
     * @return mixed
     */
    public function getOsd()
    {
        return $this->osd;
    }

    /**
     * @param mixed $osd
     */
    public function setOsd($osd)
    {
        $this->osd = $osd;
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

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

    public function __toString()
    {
        return $this->title;
    }
}

