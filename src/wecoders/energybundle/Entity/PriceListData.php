<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PriceListData
 *
 * @ORM\Table(name="price_list_data")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\PriceListDataRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PriceListData
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\PriceList", inversedBy="priceListDatas")
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
     * @var string
     *
     * @ORM\Column(name="tariff_type_code", type="string", length=100)
     */
    private $tariffTypeCode;

    /**
     * @return string
     */
    public function getTariffTypeCode()
    {
        return $this->tariffTypeCode;
    }

    /**
     * @param string $tariffTypeCode
     */
    public function setTariffTypeCode($tariffTypeCode)
    {
        $this->tariffTypeCode = $tariffTypeCode;
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
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\PriceListDataAndTariff", mappedBy="priceListData", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $priceListDataAndTariffs;

    public function addPriceListDataAndTariff(PriceListDataAndTariff $priceListDataAndTariff)
    {
        $this->priceListDataAndTariffs[] = $priceListDataAndTariff;
        $priceListDataAndTariff->setPriceListData($this);

        return $this;
    }

    public function removePriceListDataAndTariff(PriceListDataAndTariff $priceListDataAndTariff)
    {
        $this->priceListDataAndTariffs->removeElement($priceListDataAndTariff);
    }

    public function getPriceListDataAndTariffs()
    {
        return $this->priceListDataAndTariffs;
    }

    public function setPriceListDataAndTariffs($priceListDataAndTariffs)
    {
        $this->priceListDataAndTariffs = $priceListDataAndTariffs;
    }

    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice", mappedBy="priceListData", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $priceListDataAndYearWithPrices;

    public function addPriceListDataAndYearWithPrice(PriceListDataAndYearWithPrice $priceListDataAndYearWithPrice)
    {
        $this->priceListDataAndYearWithPrices[] = $priceListDataAndYearWithPrice;
        $priceListDataAndYearWithPrice->setPriceListData($this);

        return $this;
    }

    public function removePriceListDataAndYearWithPrice(PriceListDataAndYearWithPrice $priceListDataAndYearWithPrice)
    {
        $this->priceListDataAndYearWithPrices->removeElement($priceListDataAndYearWithPrice);
    }

    public function getPriceListDataAndYearWithPrices()
    {
        return $this->priceListDataAndYearWithPrices;
    }

    public function setPriceListDataAndYearWithPrices($priceListDataAndYearWithPrices)
    {
        $this->priceListDataAndYearWithPrices = $priceListDataAndYearWithPrices;
    }
}

