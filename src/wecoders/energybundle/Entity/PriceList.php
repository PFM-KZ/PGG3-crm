<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Service\EnergyTypeModel;

/**
 * PriceList
 *
 * @ORM\Table(name="price_list")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\PriceListGroupRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PriceList
{
    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\PriceListAndServiceData", mappedBy="priceList", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $priceListAndServiceDatas;

    public function addPriceListAndServiceData(PriceListAndServiceData $priceListAndServiceData)
    {
        $this->priceListAndServiceDatas[] = $priceListAndServiceData;
        $priceListAndServiceData->setPriceList($this);

        return $this;
    }

    public function removePriceListAndServiceData(PriceListAndServiceData $priceListAndServiceData)
    {
        $this->priceListAndServiceDatas->removeElement($priceListAndServiceData);
    }

    public function getPriceListAndServiceDatas()
    {
        return $this->priceListAndServiceDatas;
    }

    public function setPriceListAndServiceDatas($priceListAndServiceDatas)
    {
        $this->priceListAndServiceDatas = $priceListAndServiceDatas;
    }
    
    /**
     * @var string
     *
     * @ORM\Column(name="show_in_authorization", type="boolean")
     */
    protected $showInAuthorization;

    /**
     * @return string
     */
    public function getShowInAuthorization()
    {
        return $this->showInAuthorization;
    }

    /**
     * @param string $showInAuthorization
     */
    public function setShowInAuthorization($showInAuthorization)
    {
        $this->showInAuthorization = $showInAuthorization;
    }

    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\PriceListData", mappedBy="priceList", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $priceListDatas;

    public function addPriceListData(PriceListData $priceListData)
    {
        $this->priceListDatas[] = $priceListData;
        $priceListData->setPriceList($this);

        return $this;
    }

    public function removePriceListData(PriceListData $priceListData)
    {
        $this->priceListDatas->removeElement($priceListData);
    }

    public function getPriceListDatas()
    {
        return $this->priceListDatas;
    }

    public function setPriceListDatas($priceListDatas)
    {
        $this->priceListDatas = $priceListDatas;
    }

    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\PriceListSubscription", mappedBy="priceList", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $priceListSubscriptions;

    public function addPriceListSubscription(PriceListSubscription $priceListSubscription)
    {
        $this->priceListSubscriptions[] = $priceListSubscription;
        $priceListSubscription->setPriceList($this);

        return $this;
    }

    public function removePriceListSubscription(PriceListSubscription $priceListSubscription)
    {
        $this->priceListSubscriptions->removeElement($priceListSubscription);
    }

    public function getPriceListSubscriptions()
    {
        return $this->priceListSubscriptions;
    }

    public function setPriceListSubscriptions($priceListSubscriptions)
    {
        $this->priceListSubscriptions = $priceListSubscriptions;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\PriceListGroup")
     * @ORM\JoinColumn(name="price_list_group_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $priceListGroup;

    /**
     * @return mixed
     */
    public function getPriceListGroup()
    {
        return $this->priceListGroup;
    }

    /**
     * @param mixed $priceListGroup
     */
    public function setPriceListGroup($priceListGroup)
    {
        $this->priceListGroup = $priceListGroup;
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="energy_type", type="integer", nullable=true)
     */
    protected $energyType;

    /**
     * @return string
     */
    public function getEnergyType()
    {
        return $this->energyType;
    }

    /**
     * @param string $energyType
     */
    public function setEnergyType($energyType)
    {
        $this->energyType = $energyType;
    }

    /**
     * @var float
     *
     * @ORM\Column(name="oh_net_value", type="string", length=255, nullable=true)
     */
    private $feeOhNetValue;

    /**
     * @var float
     *
     * @ORM\Column(name="oh_gross_value", type="string", length=255, nullable=true)
     */
    private $feeOhGrossValue;

    /**
     * @var float
     *
     * @ORM\Column(name="oze_net_value", type="string", length=255, nullable=true)
     */
    private $feeOzeNetValue;

    /**
     * @var float
     *
     * @ORM\Column(name="oze_gross_value", type="string", length=255, nullable=true)
     */
    private $feeOzeGrossValue;

    /**
     * @var float
     *
     * @ORM\Column(name="ud_net_value", type="string", length=255, nullable=true)
     */
    private $feeUdNetValue;

    /**
     * @var float
     *
     * @ORM\Column(name="ud_gross_value", type="string", length=255, nullable=true)
     */
    private $feeUdGrossValue;

    /**
     * @var float
     *
     * @ORM\Column(name="gsc_net_value", type="string", length=255, nullable=true)
     */
    private $feeGscNetValue;

    /**
     * @var float
     *
     * @ORM\Column(name="gsc_gross_value", type="string", length=255, nullable=true)
     */
    private $feeGscGrossValue;

    /**
     * @return float
     */
    public function getFeeGscNetValue()
    {
        return $this->feeGscNetValue;
    }

    /**
     * @param float $feeGscNetValue
     */
    public function setFeeGscNetValue($feeGscNetValue)
    {
        $this->feeGscNetValue = $feeGscNetValue;
    }

    /**
     * @return float
     */
    public function getFeeGscGrossValue()
    {
        return $this->feeGscGrossValue;
    }

    /**
     * @param float $feeGscGrossValue
     */
    public function setFeeGscGrossValue($feeGscGrossValue)
    {
        $this->feeGscGrossValue = $feeGscGrossValue;
    }

    /**
     * @var float
     *
     * @ORM\Column(name="rebate_marketing_agreement_net_value", type="string", length=255, nullable=true)
     */
    private $rebateMarketingAgreementNetValue;

    /**
     * @var float
     *
     * @ORM\Column(name="rebate_marketing_agreement_gross_value", type="string", length=255, nullable=true)
     */
    private $rebateMarketingAgreementGrossValue;

    /**
     * @var float
     *
     * @ORM\Column(name="rebate_timely_payments_net_value", type="string", length=255, nullable=true)
     */
    private $rebateTimelyPaymentsNetValue;

    /**
     * @var float
     *
     * @ORM\Column(name="rebate_timely_payments_gross_value", type="string", length=255, nullable=true)
     */
    private $rebateTimelyPaymentsGrossValue;

    /**
     * @var float
     *
     * @ORM\Column(name="rebate_electronic_invoice_net_value", type="string", length=255, nullable=true)
     */
    private $rebateElectronicInvoiceNetValue;

    /**
     * @var float
     *
     * @ORM\Column(name="rebate_electronic_invoice_gross_value", type="string", length=255, nullable=true)
     */
    private $rebateElectronicInvoiceGrossValue;

    /**
     * @var float
     *
     * @ORM\Column(name="date_of_payment_days", type="integer")
     */
    private $dateOfPaymentDays;

    /**
     * @var float
     *
     * @ORM\Column(name="correction_date_of_payment_days", type="integer")
     */
    private $correctionDateOfPaymentDays;

    /**
     * @return float
     */
    public function getDateOfPaymentDays()
    {
        return $this->dateOfPaymentDays;
    }

    /**
     * @param float $dateOfPaymentDays
     */
    public function setDateOfPaymentDays($dateOfPaymentDays)
    {
        $this->dateOfPaymentDays = $dateOfPaymentDays;
    }

    /**
     * @return float
     */
    public function getCorrectionDateOfPaymentDays()
    {
        return $this->correctionDateOfPaymentDays;
    }

    /**
     * @param float $correctionDateOfPaymentDays
     */
    public function setCorrectionDateOfPaymentDays($correctionDateOfPaymentDays)
    {
        $this->correctionDateOfPaymentDays = $correctionDateOfPaymentDays;
    }

    /**
     * @return float
     */
    public function getRebateMarketingAgreementNetValue()
    {
        return $this->rebateMarketingAgreementNetValue;
    }

    /**
     * @param float $rebateMarketingAgreementNetValue
     */
    public function setRebateMarketingAgreementNetValue($rebateMarketingAgreementNetValue)
    {
        $this->rebateMarketingAgreementNetValue = $rebateMarketingAgreementNetValue;
    }

    /**
     * @return float
     */
    public function getRebateMarketingAgreementGrossValue()
    {
        return $this->rebateMarketingAgreementGrossValue;
    }

    /**
     * @param float $rebateMarketingAgreementGrossValue
     */
    public function setRebateMarketingAgreementGrossValue($rebateMarketingAgreementGrossValue)
    {
        $this->rebateMarketingAgreementGrossValue = $rebateMarketingAgreementGrossValue;
    }

    /**
     * @return float
     */
    public function getRebateTimelyPaymentsNetValue()
    {
        return $this->rebateTimelyPaymentsNetValue;
    }

    /**
     * @param float $rebateTimelyPaymentsNetValue
     */
    public function setRebateTimelyPaymentsNetValue($rebateTimelyPaymentsNetValue)
    {
        $this->rebateTimelyPaymentsNetValue = $rebateTimelyPaymentsNetValue;
    }

    /**
     * @return float
     */
    public function getRebateTimelyPaymentsGrossValue()
    {
        return $this->rebateTimelyPaymentsGrossValue;
    }

    /**
     * @param float $rebateTimelyPaymentsGrossValue
     */
    public function setRebateTimelyPaymentsGrossValue($rebateTimelyPaymentsGrossValue)
    {
        $this->rebateTimelyPaymentsGrossValue = $rebateTimelyPaymentsGrossValue;
    }

    /**
     * @return float
     */
    public function getRebateElectronicInvoiceNetValue()
    {
        return $this->rebateElectronicInvoiceNetValue;
    }

    /**
     * @param float $rebateElectronicInvoiceNetValue
     */
    public function setRebateElectronicInvoiceNetValue($rebateElectronicInvoiceNetValue)
    {
        $this->rebateElectronicInvoiceNetValue = $rebateElectronicInvoiceNetValue;
    }

    /**
     * @return float
     */
    public function getRebateElectronicInvoiceGrossValue()
    {
        return $this->rebateElectronicInvoiceGrossValue;
    }

    /**
     * @param float $rebateElectronicInvoiceGrossValue
     */
    public function setRebateElectronicInvoiceGrossValue($rebateElectronicInvoiceGrossValue)
    {
        $this->rebateElectronicInvoiceGrossValue = $rebateElectronicInvoiceGrossValue;
    }

    /**
     * @return float
     */
    public function getFeeOhNetValue()
    {
        return $this->feeOhNetValue;
    }

    /**
     * @param float $feeOhNetValue
     */
    public function setFeeOhNetValue($feeOhNetValue)
    {
        $this->feeOhNetValue = $feeOhNetValue;
    }

    /**
     * @return float
     */
    public function getFeeOhGrossValue()
    {
        return $this->feeOhGrossValue;
    }

    /**
     * @param float $feeOhGrossValue
     */
    public function setFeeOhGrossValue($feeOhGrossValue)
    {
        $this->feeOhGrossValue = $feeOhGrossValue;
    }

    /**
     * @return float
     */
    public function getFeeOzeNetValue()
    {
        return $this->feeOzeNetValue;
    }

    /**
     * @param float $feeOzeNetValue
     */
    public function setFeeOzeNetValue($feeOzeNetValue)
    {
        $this->feeOzeNetValue = $feeOzeNetValue;
    }

    /**
     * @return float
     */
    public function getFeeOzeGrossValue()
    {
        return $this->feeOzeGrossValue;
    }

    /**
     * @param float $feeOzeGrossValue
     */
    public function setFeeOzeGrossValue($feeOzeGrossValue)
    {
        $this->feeOzeGrossValue = $feeOzeGrossValue;
    }

    /**
     * @return float
     */
    public function getFeeUdNetValue()
    {
        return $this->feeUdNetValue;
    }

    /**
     * @param float $feeUdNetValue
     */
    public function setFeeUdNetValue($feeUdNetValue)
    {
        $this->feeUdNetValue = $feeUdNetValue;
    }

    /**
     * @return float
     */
    public function getFeeUdGrossValue()
    {
        return $this->feeUdGrossValue;
    }

    /**
     * @param float $feeUdGrossValue
     */
    public function setFeeUdGrossValue($feeUdGrossValue)
    {
        $this->feeUdGrossValue = $feeUdGrossValue;
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
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

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
        return ($this->energyType ? EnergyTypeModel::getOptionByValue($this->energyType) : '') . ' ' .
            ($this->priceListGroup ? $this->priceListGroup : '') . ' ' .
            $this->title;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        if ($this->showInAuthorization == null) {
            $this->showInAuthorization = false;
        }
    }
}

