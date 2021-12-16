<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use GCRM\CRMBundle\Repository\ContractGasAndPriceListRepository;

/**
 * ContractGasAndPriceList
 *
 * @ORM\Table(name="contract_gas_and_price_list")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ContractGasAndPriceListRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ContractGasAndPriceList implements ContractAndPriceListInterface
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\ContractGas", inversedBy="contractAndPriceLists")
     * @ORM\JoinColumn(name="contract_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $contract;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $priceList;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="from_date", type="datetime", nullable=true)
     */
    private $fromDate;

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
     * @return mixed
     */
    public function getContract()
    {
        return $this->contract;
    }

    /**
     * @param mixed $contract
     */
    public function setContract($contract)
    {
        $this->contract = $contract;
    }

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

}

