<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * PriceListAndServiceData
 *
 * @ORM\Table(name="price_list_and_service_data")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\PriceListRepository")
 */
class PriceListAndServiceData
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
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\PriceList", inversedBy="priceListAndServiceDatas")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $priceList;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $service;

    /**
     * @return mixed
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @param mixed $priceList
     */
    public function setPriceList($priceList)
    {
        $this->priceList = $priceList;
    }

    /**
     * @return mixed
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param mixed $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

}

