<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PriceListDataAndTariff
 *
 * @ORM\Table(name="price_list_data_and_tariff")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\PriceListRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PriceListDataAndTariff
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\PriceListData", inversedBy="priceListDatas")
     * @ORM\JoinColumn(name="price_list_data_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $priceListData;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\Tariff")
     * @ORM\JoinColumn(name="tariff_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $tariff;

    /**
     * @return mixed
     */
    public function getPriceListData()
    {
        return $this->priceListData;
    }

    /**
     * @param mixed $priceListData
     */
    public function setPriceListData($priceListData)
    {
        $this->priceListData = $priceListData;
    }

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

}
