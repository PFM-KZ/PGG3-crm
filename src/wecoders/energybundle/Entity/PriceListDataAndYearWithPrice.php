<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PriceListDataAndYearWithPrice
 *
 * @ORM\Table(name="price_list_data_and_year_with_price")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\PriceListRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PriceListDataAndYearWithPrice
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\PriceListData", inversedBy="priceListDatas")
     * @ORM\JoinColumn(name="price_list_data_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $priceListData;

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
     * @var integer
     *
     * @ORM\Column(name="year", type="integer", nullable=true)
     */
    private $year;

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
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @param int $year
     */
    public function setYear($year)
    {
        $this->year = $year;
    }

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

