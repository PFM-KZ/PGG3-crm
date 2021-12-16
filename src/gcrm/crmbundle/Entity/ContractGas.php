<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Wecoders\EnergyBundle\Entity\ContractEnergyInterface;

/**
 * ContractGas
 *
 * @ORM\Table(name="contract_gas")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ContractGasRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ContractGas extends ContractEnergyBase implements ContractInterface, ContractEnergyInterface
{
    const TYPE = 'GAS';

    public function getFirstValueOfTwoFieldsPpCodeAndCounterNr()
    {
        if ($this->getMeasuringSystemId()) {
            return $this->getMeasuringSystemId();
        }
        if ($this->getGasMeterFabricNr()) {
            return $this->getGasMeterFabricNr();
        }
        return null;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string")
     */
    protected $type = 'GAS';

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractGasAndPriceList", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $contractAndPriceLists;

    public function addContractAndPriceList(ContractGasAndPriceList $contractAndPriceList)
    {
        $this->contractAndPriceLists[] = $contractAndPriceList;
        $contractAndPriceList->setContract($this);

        return $this;
    }

    public function removeContractAndPriceList(ContractGasAndPriceList $contractAndPriceList)
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
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractGasAndDistributionTariff", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $contractAndDistributionTariffs;

    public function addContractAndDistributionTariff(ContractGasAndDistributionTariff $contractAndDistributionTariff)
    {
        $this->contractAndDistributionTariffs[] = $contractAndDistributionTariff;
        $contractAndDistributionTariff->setContract($this);

        return $this;
    }

    public function removeContractAndDistributionTariff(ContractGasAndDistributionTariff $contractAndDistributionTariff)
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
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractGasAndSellerTariff", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $contractAndSellerTariffs;

    public function addContractAndSellerTariff(ContractGasAndSellerTariff $contractAndSellerTariff)
    {
        $this->contractAndSellerTariffs[] = $contractAndSellerTariff;
        $contractAndSellerTariff->setContract($this);

        return $this;
    }

    public function removeContractAndSellerTariff(ContractGasAndSellerTariff $contractAndSellerTariff)
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
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractGasAndPpCode", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $contractAndPpCodes;

    public function addContractAndPpCode(ContractGasAndPpCode $contractAndPpCode)
    {
        $this->contractAndPpCodes[] = $contractAndPpCode;
        $contractAndPpCode->setContract($this);

        return $this;
    }

    public function removeContractAndPpCode(ContractGasAndPpCode $contractAndPpCode)
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
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\Osd")
     * @ORM\JoinColumn(name="osd_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $osd;

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
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\RecordingGasAttachment", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $recordingAttachments;

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractGasAttachment", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
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
     * @ORM\Column(name="measuring_system_id", type="string", nullable=true)
     */
    protected $measuringSystemId;

    /**
     * @var string
     *
     * @ORM\Column(name="gas_meter_fabric_nr", type="string", nullable=true)
     */
    protected $gasMeterFabricNr;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="contractual_year_from", type="date", nullable=true)
     */
    protected $contractualYearFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="contractual_year_to", type="date", nullable=true)
     */
    protected $contractualYearTo;

    /**
     * @var string
     *
     * @ORM\Column(name="period_of_notice", type="string", nullable=true)
     */
    protected $periodOfNotice;

    /**
     * @var string
     *
     * @ORM\Column(name="previous_seller_tariff", type="string", nullable=true)
     */
    protected $previousSellerTariff;

    /**
     * @var string
     *
     * @ORM\Column(name="osd_tariff", type="string", nullable=true)
     */
    protected $osdTariff;

    /**
     * @var string
     *
     * @ORM\Column(name="pep_tariff", type="string", nullable=true)
     */
    protected $pepTariff;

    /**
     * @var string
     *
     * @ORM\Column(name="psg_branch", type="string", nullable=true)
     */
    protected $psgBranch;

    /**
     * @return mixed
     */
    public function getMeasuringSystemId()
    {
        return $this->measuringSystemId;
    }

    /**
     * @param mixed $measuringSystemId
     */
    public function setMeasuringSystemId($measuringSystemId)
    {
        $this->measuringSystemId = $measuringSystemId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGasMeterFabricNr()
    {
        return $this->gasMeterFabricNr;
    }

    /**
     * @param mixed $gasMeterFabricNr
     */
    public function setGasMeterFabricNr($gasMeterFabricNr)
    {
        $this->gasMeterFabricNr = $gasMeterFabricNr;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContractualYearFrom()
    {
        return $this->contractualYearFrom;
    }

    /**
     * @param mixed $contractualYearFrom
     */
    public function setContractualYearFrom($contractualYearFrom)
    {
        $this->contractualYearFrom = $contractualYearFrom;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContractualYearTo()
    {
        return $this->contractualYearTo;
    }

    /**
     * @param mixed $contractualYearTo
     */
    public function setContractualYearTo($contractualYearTo)
    {
        $this->contractualYearTo = $contractualYearTo;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContractFromDate()
    {
        return $this->contractFromDate;
    }

    /**
     * @return mixed
     */
    public function getPeriodOfNotice()
    {
        return $this->periodOfNotice;
    }

    /**
     * @param mixed $periodOfNotice
     */
    public function setPeriodOfNotice($periodOfNotice)
    {
        $this->periodOfNotice = $periodOfNotice;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPreviousSellerTariff()
    {
        return $this->previousSellerTariff;
    }

    /**
     * @param mixed $previousSellerTariff
     */
    public function setPreviousSellerTariff($previousSellerTariff)
    {
        $this->previousSellerTariff = $previousSellerTariff;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOsdTariff()
    {
        return $this->osdTariff;
    }

    /**
     * @param mixed $osdTariff
     */
    public function setOsdTariff($osdTariff)
    {
        $this->osdTariff = $osdTariff;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPepTariff()
    {
        return $this->pepTariff;
    }

    /**
     * @param mixed $pepTariff
     */
    public function setPepTariff($pepTariff)
    {
        $this->pepTariff = $pepTariff;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPsgBranch()
    {
        return $this->psgBranch;
    }

    /**
     * @param mixed $psgBranch
     */
    public function setPsgBranch($psgBranch)
    {
        $this->psgBranch = $psgBranch;

        return $this;
    }

}

