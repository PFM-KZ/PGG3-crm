<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Wecoders\EnergyBundle\Entity\ContractEnergyInterface;

/**
 * ContractEnergy
 *
 * @ORM\Table(name="contract_energy")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ContractEnergyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ContractEnergy extends ContractEnergyBase implements ContractInterface, ContractEnergyInterface
{
    const TYPE = 'ENERGY';

    public function getFirstValueOfTwoFieldsPpCodeAndCounterNr()
    {
        if ($this->getPpCode()) {
            return $this->getPpCode();
        }
        if ($this->getPpCounterNr()) {
            return $this->getPpCounterNr();
        }
        return null;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string")
     */
    protected $type = 'ENERGY';

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractEnergyAndPriceList", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $contractAndPriceLists;

    public function addContractAndPriceList(ContractEnergyAndPriceList $contractAndPriceList)
    {
        $this->contractAndPriceLists[] = $contractAndPriceList;
        $contractAndPriceList->setContract($this);

        return $this;
    }

    public function removeContractAndPriceList(ContractEnergyAndPriceList $contractAndPriceList)
    {
        $this->contractAndPriceLists->removeElement($contractAndPriceList);
    }

    public function getContractAndPriceLists()
    {
        return $this->contractAndPriceLists;
    }

    public function setContractAndPriceLists($contractAndPriceLists)
    {
        $this->contractAndPriceLists = $contractAndPriceLists;
    }

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractEnergyAndDistributionTariff", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $contractAndDistributionTariffs;

    public function addContractAndDistributionTariff(ContractEnergyAndDistributionTariff $contractAndDistributionTariff)
    {
        $this->contractAndDistributionTariffs[] = $contractAndDistributionTariff;
        $contractAndDistributionTariff->setContract($this);

        return $this;
    }

    public function removeContractAndDistributionTariff(ContractEnergyAndDistributionTariff $contractAndDistributionTariff)
    {
        $this->contractAndDistributionTariffs->removeElement($contractAndDistributionTariff);
    }

    public function getContractAndDistributionTariffs()
    {
        return $this->contractAndDistributionTariffs;
    }

    public function setContractAndDistributionTariffs($contractAndDistributionTariffs)
    {
        $this->contractAndDistributionTariffs = $contractAndDistributionTariffs;
    }

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractEnergyAndSellerTariff", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $contractAndSellerTariffs;

    public function addContractAndSellerTariff(ContractEnergyAndSellerTariff $contractAndSellerTariff)
    {
        $this->contractAndSellerTariffs[] = $contractAndSellerTariff;
        $contractAndSellerTariff->setContract($this);

        return $this;
    }

    public function removeContractAndSellerTariff(ContractEnergyAndSellerTariff $contractAndSellerTariff)
    {
        $this->contractAndSellerTariffs->removeElement($contractAndSellerTariff);
    }

    public function getContractAndSellerTariffs()
    {
        return $this->contractAndSellerTariffs;
    }

    public function setContractAndSellerTariffs($contractAndSellerTariffs)
    {
        $this->contractAndSellerTariffs = $contractAndSellerTariffs;
    }

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractEnergyAndPpCode", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $contractAndPpCodes;

    public function addContractAndPpCode(ContractEnergyAndPpCode $contractAndPpCode)
    {
        $this->contractAndPpCodes[] = $contractAndPpCode;
        $contractAndPpCode->setContract($this);

        return $this;
    }

    public function removeContractAndPpCode(ContractEnergyAndPpCode $contractAndPpCode)
    {
        $this->contractAndPpCodes->removeElement($contractAndPpCode);
    }

    public function getContractAndPpCodes()
    {
        return $this->contractAndPpCodes;
    }

    public function setContractAndPpCodes($contractAndPpCodes)
    {
        $this->contractAndPpCodes = $contractAndPpCodes;
    }

    public function getSellerTariffByDate($date)
    {
        return $this->getAssociatedObjectByDate($this->contractAndSellerTariffs, $date, 'getTariff', 'getFromDate');
    }

    public function getDistributionTariffByDate($date)
    {
        return $this->getAssociatedObjectByDate($this->contractAndDistributionTariffs, $date, 'getTariff', 'getFromDate');
    }

    public function getPpCodeByDate($date)
    {
        return $this->getAssociatedObjectByDate($this->contractAndPpCodes, $date, 'getPpCode', 'getFromDate');
    }

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\RecordingEnergyAttachment", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $recordingAttachments;

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractEnergyAttachment", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $contractAttachments;

    public function __construct()
    {
        $this->contractAttachments = new ArrayCollection();
        $this->recordingAttachments = new ArrayCollection();
    }

    /**
     * @var string
     *
     * @ORM\Column(name="period_of_notice", type="string", nullable=true)
     */
    private $periodOfNotice;

    /**
     * @var string
     *
     * @ORM\Column(name="product", type="string", nullable=true)
     */
    private $product;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_counter_nr", type="string", nullable=true)
     */
    private $ppCounterNr;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_registration_nr", type="string", nullable=true)
     */
    private $ppRegistrationNr;

    /**
     * @var string
     *
     * @ORM\Column(name="proxy", type="string", nullable=true)
     */
    private $proxy;

    /**
     * @return string
     */
    public function getPeriodOfNotice()
    {
        return $this->periodOfNotice;
    }

    /**
     * @param string $periodOfNotice
     */
    public function setPeriodOfNotice($periodOfNotice)
    {
        $this->periodOfNotice = $periodOfNotice;

        return $this;
    }

    /**
     * @return string
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param string $product
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return string
     */
    public function getPpCounterNr()
    {
        return $this->ppCounterNr;
    }

    /**
     * @param string $ppCounterNr
     */
    public function setPpCounterNr($ppCounterNr)
    {
        $this->ppCounterNr = $ppCounterNr;
    }

    /**
     * @return string
     */
    public function getPpRegistrationNr()
    {
        return $this->ppRegistrationNr;
    }

    /**
     * @param string $ppRegistrationNr
     */
    public function setPpRegistrationNr($ppRegistrationNr)
    {
        $this->ppRegistrationNr = $ppRegistrationNr;
    }

    /**
     * @return string
     */
    public function getConsumption()
    {
        return $this->consumption;
    }

    /**
     * @param string $consumption
     */
    public function setConsumption($consumption)
    {
        $this->consumption = $consumption;
    }

    /**
     * @return mixed
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * @param mixed $proxy
     */
    public function setProxy($proxy)
    {
        $this->proxy = $proxy;

        return $this;
    }

}

