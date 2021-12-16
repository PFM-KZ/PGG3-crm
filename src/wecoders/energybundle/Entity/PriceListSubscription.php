<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PriceListSubscription
 *
 * @ORM\Table(name="price_list_subscription")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\PriceListRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PriceListSubscription
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\PriceList", inversedBy="priceListSubscriptions")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $priceList;

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

    /**
     * @var float
     *
     * @ORM\Column(name="net_value", type="decimal", precision=10, scale=5, nullable=true)
     */
    private $netValue;

    /**
     * @var float
     *
     * @ORM\Column(name="gross_value", type="decimal", precision=10, scale=5, nullable=true)
     */
    private $grossValue;

    /**
     * @return float
     */
    public function getNetValue()
    {
        return $this->netValue;
    }

    /**
     * @param float $netValue
     */
    public function setNetValue($netValue)
    {
        $this->netValue = $netValue;
    }

    /**
     * @return float
     */
    public function getGrossValue()
    {
        return $this->grossValue;
    }

    /**
     * @param float $grossValue
     */
    public function setGrossValue($grossValue)
    {
        $this->grossValue = $grossValue;
    }
}

