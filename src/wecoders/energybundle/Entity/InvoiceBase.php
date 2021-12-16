<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wecoders\InvoiceBundle\Service\InvoicePathInterface;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;

/**
 * InvoiceBase
 */
class InvoiceBase implements InvoiceInterface, InvoicePathInterface, BillingDocumentInterface, GTUInterface
{
    /**
     * @var array
     *
     * @ORM\Column(name="consumption_by_device_data", type="text", nullable=true)
     */
    protected $consumptionByDeviceData;

    /**
     * Get consumptionByDeviceData
     *
     * @return array
     */
    public function getConsumptionByDeviceData()
    {
        if (!$this->consumptionByDeviceData) {
            return null;
        }

        $unserialized = json_decode($this->consumptionByDeviceData, true);

        foreach ($unserialized as &$item) {
            $item['dateFrom'] = \DateTime::createFromFormat('Y-m-d', $item['dateFrom']);
            $item['dateTo'] = \DateTime::createFromFormat('Y-m-d', $item['dateTo']);
        }

        return $unserialized;
    }

    /**
     * Set consumptionByDeviceData
     *
     * @param array consumptionByDeviceData
     *
     * @return InvoiceInterface
     */
    public function setConsumptionByDeviceData($data)
    {
        foreach ($data as &$item) {
            $item['dateFrom'] = $item['dateFrom']->format('Y-m-d');
            $item['dateTo'] = $item['dateTo']->format('Y-m-d');
        }

        $serialized = json_encode($data);
        $this->consumptionByDeviceData = $serialized;

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=true)
     */
    protected $type;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", length=255, unique=true)
     */
    protected $number;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\InvoiceBundle\Entity\InvoiceTemplate")
     * @ORM\JoinColumn(name="invoice_template_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $invoiceTemplate;

    /**
     * @return mixed
     */
    public function getInvoiceTemplate()
    {
        return $this->invoiceTemplate;
    }

    /**
     * @param mixed $invoiceTemplate
     */
    public function setInvoiceTemplate($invoiceTemplate)
    {
        $this->invoiceTemplate = $invoiceTemplate;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings")
     * @ORM\JoinColumn(name="invoice_number_settings_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $invoiceNumberSettings;

    /**
     * @return mixed
     */
    public function getInvoiceNumberSettings()
    {
        return $this->invoiceNumberSettings;
    }

    /**
     * @param mixed $invoiceNumberSettings
     */
    public function setInvoiceNumberSettings($invoiceNumberSettings)
    {
        $this->invoiceNumberSettings = $invoiceNumberSettings;
    }


    /**
     * @var string
     *
     * @ORM\Column(name="number_structure", type="string", length=255)
     */
    protected $numberStructure;

    /**
     * @var string
     *
     * @ORM\Column(name="number_leading_zeros", type="boolean")
     */
    protected $numberLeadingZeros;

    /**
     * @var string
     *
     * @ORM\Column(name="number_reset_ai_at_new_month", type="boolean")
     */
    protected $numberResetAiAtNewMonth;

    /**
     * @var string
     *
     * @ORM\Column(name="number_exclude_ai_from_leading_zeros", type="boolean")
     */
    protected $numberExcludeAiFromLeadingZeros;

    /**
     * @return string
     */
    public function getNumberExcludeAiFromLeadingZeros()
    {
        return $this->numberExcludeAiFromLeadingZeros;
    }

    /**
     * @param string $numberExcludeAiFromLeadingZeros
     */
    public function setNumberExcludeAiFromLeadingZeros($numberExcludeAiFromLeadingZeros)
    {
        $this->numberExcludeAiFromLeadingZeros = $numberExcludeAiFromLeadingZeros;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="created_in", type="string", length=255, nullable=true)
     */
    protected $createdIn;

    /**
     * @var integer
     *
     * @ORM\Column(name="billing_period", type="integer", length=6, nullable=true)
     */
    protected $billingPeriod;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="billing_period_from", type="date", nullable=true)
     */
    protected $billingPeriodFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="billing_period_to", type="date", nullable=true)
     */
    protected $billingPeriodTo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_payment", type="date")
     */
    protected $dateOfPayment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="date")
     */
    protected $createdDate;

    /**
     * @var string
     *
     * @ORM\Column(name="badge_id", type="string", length=255, nullable=true)
     */
    protected $badgeId;

    /**
     * @var string
     *
     * @ORM\Column(name="client_account_number", type="string", length=255, nullable=true)
     */
    protected $clientAccountNumber;

    /**
     * @return string
     */
    public function getClientAccountNumber()
    {
        return $this->clientAccountNumber;
    }

    /**
     * @param string $clientAccountNumber
     */
    public function setClientAccountNumber($clientAccountNumber)
    {
        $this->clientAccountNumber = $clientAccountNumber;
    }

    /**
     * @return string
     */
    public function getBadgeId()
    {
        return $this->badgeId;
    }

    /**
     * @param string $badgeId
     */
    public function setBadgeId($badgeId)
    {
        $this->badgeId = $badgeId;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="contract_number", type="string", length=255, nullable=true)
     */
    protected $contractNumber;

    /**
     * @return string
     */
    public function getContractNumber()
    {
        return $this->contractNumber;
    }

    /**
     * @param string $contractNumber
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="seller_title", type="string", length=255, nullable=true)
     */
    protected $sellerTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_address", type="string", length=255, nullable=true)
     */
    protected $sellerAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_zip_code", type="string", length=255, nullable=true)
     */
    protected $sellerZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_city", type="string", length=255, nullable=true)
     */
    protected $sellerCity;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_nip", type="string", length=255, nullable=true)
     */
    protected $sellerNip;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_regon", type="string", length=255, nullable=true)
     */
    protected $sellerRegon;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_bank_name", type="string", length=255, nullable=true)
     */
    protected $sellerBankName;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_bank_account", type="string", length=255, nullable=true)
     */
    protected $sellerBankAccount;






    /**
     * @var string
     *
     * @ORM\Column(name="client_nip", type="string", length=255, nullable=true)
     */
    protected $clientNip;

    /**
     * @var string
     *
     * @ORM\Column(name="client_pesel", type="string", length=255, nullable=true)
     */
    protected $clientPesel;

    /**
     * @var string
     *
     * @ORM\Column(name="client_full_name", type="string", length=255, nullable=true)
     */
    protected $clientFullName;

    /**
     * @var string
     *
     * @ORM\Column(name="client_street", type="string", length=255, nullable=true)
     */
    protected $clientStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="client_house_nr", type="string", length=255, nullable=true)
     */
    protected $clientHouseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="client_apartment_nr", type="string", length=255, nullable=true)
     */
    protected $clientApartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="client_zip_code", type="string", length=255, nullable=true)
     */
    protected $clientZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="client_city", type="string", length=255, nullable=true)
     */
    protected $clientCity;







    /**
     * @var string
     *
     * @ORM\Column(name="recipient_company_name", type="string", length=255, nullable=true)
     */
    protected $recipientCompanyName;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_nip", type="string", length=255, nullable=true)
     */
    protected $recipientNip;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_street", type="string", length=255, nullable=true)
     */
    protected $recipientStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_house_nr", type="string", length=255, nullable=true)
     */
    protected $recipientHouseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_apartment_nr", type="string", length=255, nullable=true)
     */
    protected $recipientApartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_zip_code", type="string", length=255, nullable=true)
     */
    protected $recipientZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_city", type="string", length=255, nullable=true)
     */
    protected $recipientCity;

    /**
     * @return string
     */
    public function getRecipientCompanyName()
    {
        return $this->recipientCompanyName;
    }

    /**
     * @param string $recipientCompanyName
     */
    public function setRecipientCompanyName($recipientCompanyName)
    {
        $this->recipientCompanyName = $recipientCompanyName;
    }

    /**
     * @return string
     */
    public function getRecipientNip()
    {
        return $this->recipientNip;
    }

    /**
     * @param string $recipientNip
     */
    public function setRecipientNip($recipientNip)
    {
        $this->recipientNip = $recipientNip;
    }

    /**
     * @return string
     */
    public function getRecipientStreet()
    {
        return $this->recipientStreet;
    }

    /**
     * @param string $recipientStreet
     */
    public function setRecipientStreet($recipientStreet)
    {
        $this->recipientStreet = $recipientStreet;
    }

    /**
     * @return string
     */
    public function getRecipientHouseNr()
    {
        return $this->recipientHouseNr;
    }

    /**
     * @param string $recipientHouseNr
     */
    public function setRecipientHouseNr($recipientHouseNr)
    {
        $this->recipientHouseNr = $recipientHouseNr;
    }

    /**
     * @return string
     */
    public function getRecipientApartmentNr()
    {
        return $this->recipientApartmentNr;
    }

    /**
     * @param string $recipientApartmentNr
     */
    public function setRecipientApartmentNr($recipientApartmentNr)
    {
        $this->recipientApartmentNr = $recipientApartmentNr;
    }

    /**
     * @return string
     */
    public function getRecipientZipCode()
    {
        return $this->recipientZipCode;
    }

    /**
     * @param string $recipientZipCode
     */
    public function setRecipientZipCode($recipientZipCode)
    {
        $this->recipientZipCode = $recipientZipCode;
    }

    /**
     * @return string
     */
    public function getRecipientCity()
    {
        return $this->recipientCity;
    }

    /**
     * @param string $recipientCity
     */
    public function setRecipientCity($recipientCity)
    {
        $this->recipientCity = $recipientCity;
    }








    /**
     * @var string
     *
     * @ORM\Column(name="payer_company_name", type="string", length=255, nullable=true)
     */
    protected $payerCompanyName;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_nip", type="string", length=255, nullable=true)
     */
    protected $payerNip;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_street", type="string", length=255, nullable=true)
     */
    protected $payerStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_house_nr", type="string", length=255, nullable=true)
     */
    protected $payerHouseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_apartment_nr", type="string", length=255, nullable=true)
     */
    protected $payerApartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_zip_code", type="string", length=255, nullable=true)
     */
    protected $payerZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_city", type="string", length=255, nullable=true)
     */
    protected $payerCity;

    /**
     * @return string
     */
    public function getPayerCompanyName()
    {
        return $this->payerCompanyName;
    }

    /**
     * @param string $payerCompanyName
     */
    public function setPayerCompanyName($payerCompanyName)
    {
        $this->payerCompanyName = $payerCompanyName;
    }

    /**
     * @return string
     */
    public function getPayerNip()
    {
        return $this->payerNip;
    }

    /**
     * @param string $payerNip
     */
    public function setPayerNip($payerNip)
    {
        $this->payerNip = $payerNip;
    }

    /**
     * @return string
     */
    public function getPayerStreet()
    {
        return $this->payerStreet;
    }

    /**
     * @param string $payerStreet
     */
    public function setPayerStreet($payerStreet)
    {
        $this->payerStreet = $payerStreet;
    }

    /**
     * @return string
     */
    public function getPayerHouseNr()
    {
        return $this->payerHouseNr;
    }

    /**
     * @param string $payerHouseNr
     */
    public function setPayerHouseNr($payerHouseNr)
    {
        $this->payerHouseNr = $payerHouseNr;
    }

    /**
     * @return string
     */
    public function getPayerApartmentNr()
    {
        return $this->payerApartmentNr;
    }

    /**
     * @param string $payerApartmentNr
     */
    public function setPayerApartmentNr($payerApartmentNr)
    {
        $this->payerApartmentNr = $payerApartmentNr;
    }

    /**
     * @return string
     */
    public function getPayerZipCode()
    {
        return $this->payerZipCode;
    }

    /**
     * @param string $payerZipCode
     */
    public function setPayerZipCode($payerZipCode)
    {
        $this->payerZipCode = $payerZipCode;
    }

    /**
     * @return string
     */
    public function getPayerCity()
    {
        return $this->payerCity;
    }

    /**
     * @param string $payerCity
     */
    public function setPayerCity($payerCity)
    {
        $this->payerCity = $payerCity;
    }






    /**
     * @var string
     *
     * @ORM\Column(name="pp_name", type="string", length=255, nullable=true)
     */
    protected $ppName;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_street", type="string", length=255, nullable=true)
     */
    protected $ppStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_zip_code", type="string", length=255, nullable=true)
     */
    protected $ppZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_city", type="string", length=255, nullable=true)
     */
    protected $ppCity;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_house_nr", type="string", length=255, nullable=true)
     */
    protected $ppHouseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_apartment_nr", type="string", length=255, nullable=true)
     */
    protected $ppApartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_energy", type="string", length=255, nullable=true)
     */
    protected $ppEnergy;

    /**
     * @return string
     */
    public function getPpName()
    {
        return $this->ppName;
    }

    /**
     * @param string $ppName
     */
    public function setPpName($ppName)
    {
        $this->ppName = $ppName;
    }

    /**
     * @return string
     */
    public function getPpEnergy()
    {
        return $this->ppEnergy;
    }

    /**
     * @param string $ppEnergy
     */
    public function setPpEnergy($ppEnergy)
    {
        $this->ppEnergy = $ppEnergy;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="tariff", type="string", length=255, nullable=true)
     */
    protected $tariff;

    /**
     * @return string
     */
    public function getTariff()
    {
        return $this->tariff;
    }

    /**
     * @param string $tariff
     */
    public function setTariff($tariff)
    {
        $this->tariff = $tariff;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="distribution_tariff", type="string", length=255, nullable=true)
     */
    protected $distributionTariff;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_tariff", type="string", length=255, nullable=true)
     */
    protected $sellerTariff;

    /**
     * @return string
     */
    public function getDistributionTariff()
    {
        return $this->distributionTariff;
    }

    /**
     * @param string $distributionTariff
     */
    public function setDistributionTariff($distributionTariff)
    {
        $this->distributionTariff = $distributionTariff;
    }

    /**
     * @return string
     */
    public function getSellerTariff()
    {
        return $this->sellerTariff;
    }

    /**
     * @param string $sellerTariff
     */
    public function setSellerTariff($sellerTariff)
    {
        $this->sellerTariff = $sellerTariff;
    }

    /**
     * @return string
     */
    public function getPpStreet()
    {
        return $this->ppStreet;
    }

    /**
     * @param string $ppStreet
     */
    public function setPpStreet($ppStreet)
    {
        $this->ppStreet = $ppStreet;
    }

    /**
     * @return string
     */
    public function getPpHouseNr()
    {
        return $this->ppHouseNr;
    }

    /**
     * @param string $ppHouseNr
     */
    public function setPpHouseNr($ppHouseNr)
    {
        $this->ppHouseNr = $ppHouseNr;
    }

    /**
     * @return string
     */
    public function getPpApartmentNr()
    {
        return $this->ppApartmentNr;
    }

    /**
     * @param string $ppApartmentNr
     */
    public function setPpApartmentNr($ppApartmentNr)
    {
        $this->ppApartmentNr = $ppApartmentNr;
    }

    /**
     * @return string
     */
    public function getPpZipCode()
    {
        return $this->ppZipCode;
    }

    /**
     * @param string $ppZipCode
     */
    public function setPpZipCode($ppZipCode)
    {
        $this->ppZipCode = $ppZipCode;
    }

    /**
     * @return string
     */
    public function getPpCity()
    {
        return $this->ppCity;
    }

    /**
     * @param string $ppCity
     */
    public function setPpCity($ppCity)
    {
        $this->ppCity = $ppCity;
    }

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    protected $data;


    /**
     * @var string
     *
     * @ORM\Column(name="summary_net_value", type="decimal", precision=10, scale=2, nullable=true)
     */
    protected $summaryNetValue;

    /**
     * @var string
     *
     * @ORM\Column(name="summary_gross_value", type="decimal", precision=10, scale=2, nullable=true)
     */
    protected $summaryGrossValue;

    /**
     * @var string
     *
     * @ORM\Column(name="summary_vat_value", type="decimal", precision=10, scale=2, nullable=true)
     */
    protected $summaryVatValue;

    /**
     * @var string
     *
     * @ORM\Column(name="excise", type="string", length=255, nullable=true)
     */
    protected $excise;

    /**
     * @var string
     *
     * @ORM\Column(name="excise_value", type="string", length=255, nullable=true)
     */
    protected $exciseValue;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption", type="string", length=255, nullable=true)
     */
    protected $consumption;



    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    protected $updatedAt;




    protected $calculatedSummaryGrossValue;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_paid", type="boolean", nullable=true)
     */
    protected $isPaid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="paid_value", type="decimal", precision=10, scale=2)
     */
    protected $paidValue;

    /**
     * @var boolean
     *
     * @ORM\Column(name="frozen_value", type="decimal", precision=10, scale=2)
     */
    protected $frozenValue = 0;

    /**
     * @return bool
     */
    public function getFrozenValue()
    {
        return $this->frozenValue;
    }

    /**
     * @param bool $frozenValue
     */
    public function setFrozenValue($frozenValue)
    {
        $this->frozenValue = $frozenValue;
    }

    protected $overdueDateOfPayment;

    protected $isGeneratedFileExist;

    protected $isNotActual;

    /**
     * @return mixed
     */
    public function getPaidValue()
    {
        return $this->paidValue;
    }

    /**
     * @param mixed $paidValue
     */
    public function setPaidValue($paidValue)
    {
        $this->paidValue = $paidValue;
    }

    /**
     * @return mixed
     */
    public function getIsNotActual()
    {
        return $this->isNotActual;
    }

    /**
     * @param mixed $isNotActual
     */
    public function setIsNotActual($isNotActual)
    {
        $this->isNotActual = $isNotActual;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="balance_before_invoice", type="decimal", precision=10, scale=2, nullable=true)
     */
    protected $balanceBeforeInvoice;

    /**
     * @var string
     *
     * @ORM\Column(name="balance_after_invoice", type="decimal", precision=10, scale=2, nullable=true)
     */
    protected $balanceAfterInvoice;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $client;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $seller;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_electronic", type="boolean", options={"default": 0})
     */
    protected $isElectronic;

    /**
     * @return bool
     */
    public function getIsElectronic()
    {
        return $this->isElectronic;
    }

    /**
     * @param bool $isElectronic
     */
    public function setIsElectronic($isElectronic)
    {
        $this->isElectronic = $isElectronic;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getSeller()
    {
        return $this->seller;
    }

    /**
     * @param mixed $seller
     */
    public function setSeller($seller)
    {
        $this->seller = $seller;
    }

    /**
     * @return string
     */
    public function getSellerTitle()
    {
        return $this->sellerTitle;
    }

    /**
     * @param string $sellerTitle
     */
    public function setSellerTitle($sellerTitle)
    {
        $this->sellerTitle = $sellerTitle;
    }

    /**
     * @return string
     */
    public function getSellerAddress()
    {
        return $this->sellerAddress;
    }

    /**
     * @param string $sellerAddress
     */
    public function setSellerAddress($sellerAddress)
    {
        $this->sellerAddress = $sellerAddress;
    }

    /**
     * @return string
     */
    public function getSellerZipCode()
    {
        return $this->sellerZipCode;
    }

    /**
     * @param string $sellerZipCode
     */
    public function setSellerZipCode($sellerZipCode)
    {
        $this->sellerZipCode = $sellerZipCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getSellerCity()
    {
        return $this->sellerCity;
    }

    /**
     * @param string $sellerCity
     */
    public function setSellerCity($sellerCity)
    {
        $this->sellerCity = $sellerCity;

        return $this;
    }

    /**
     * @return string
     */
    public function getSellerNip()
    {
        return $this->sellerNip;
    }

    /**
     * @param string $sellerNip
     */
    public function setSellerNip($sellerNip)
    {
        $this->sellerNip = $sellerNip;

        return $this;
    }

    /**
     * @return string
     */
    public function getSellerRegon()
    {
        return $this->sellerRegon;
    }

    /**
     * @param string $sellerRegon
     */
    public function setSellerRegon($sellerRegon)
    {
        $this->sellerRegon = $sellerRegon;
    }

    /**
     * @return string
     */
    public function getSellerBankName()
    {
        return $this->sellerBankName;
    }

    /**
     * @param string $sellerBankName
     */
    public function setSellerBankName($sellerBankName)
    {
        $this->sellerBankName = $sellerBankName;

        return $this;
    }

    /**
     * @return string
     */
    public function getSellerBankAccount()
    {
        return $this->sellerBankAccount;
    }

    /**
     * @param string $sellerBankAccount
     */
    public function setSellerBankAccount($sellerBankAccount)
    {
        $this->sellerBankAccount = $sellerBankAccount;

        return $this;
    }

    public function setConsumption($consumption)
    {
        $this->consumption = $consumption;

        return $this;
    }

    public function getConsumption()
    {
        return $this->consumption;
    }

    public function recalculateConsumption()
    {
        $data = $this->getData();
        $consumption = 0;
        foreach ($data as $key => $item) {
            if (!$item['services']) {
                return 0;
            }

            foreach ($item['services'] as $service) {
                if ($service['unit'] != 'kWh') {
                    continue;
                }

                if ($service['title'] && strpos($service['title'], 'zmienna') !== false) {
                    continue;
                }

                try {
                    $consumption += $service['quantity'];
                } catch(\Exception $e) {
                    if(preg_match('/,/', $service['quantity'])) {
                        $consumption += str_replace(',', '.', $service['quantity']);
                    } else {
                        $consumption += str_replace('.', ',', $service['quantity']);
                    }
                }
            }
        }

        $this->consumption = $consumption;
    }

    public function recalculateExciseValue()
    {
        $this->exciseValue = number_format($this->consumption * $this->excise, 2, '.', '');
    }

    /**
     * @return string
     */
    public function getExcise()
    {
        return $this->excise;
    }

    /**
     * @param string $excise
     */
    public function setExcise($excise)
    {
        $this->excise = $excise;

        return $this;
    }

    /**
     * @return string
     */
    public function getExciseValue()
    {
        return $this->exciseValue;
    }

    /**
     * @param string $exciseValue
     */
    public function setExciseValue($exciseValue)
    {
        $this->exciseValue = $exciseValue;

        return $this;
    }

    /**
     * Set data
     *
     * @param array data
     *
     * @return InvoiceInterface
     */
    public function setData($data)
    {
        $result = [];

        foreach ($data as $key => $item) {
            if (is_object($item)) {
                $item = $this->objectToData($item);
            }

            $services = [];
            foreach ($item['services'] as $service) {
                unset($service['grossValue']);
                $services[] = serialize($service);
            }
            $item['services'] = serialize($services);

            if (isset($item['rabates'])) {
                $rabates = [];
                foreach ($item['rabates'] as $rabate) {
                    unset($rabate['grossValue']);
                    $rabates[] = serialize($rabate);
                }
                $item['rabates'] = serialize($rabates);
            }

            $result[] = serialize($item);
        }

        $this->data = serialize($result);
        return $this;
    }

    private function objectToData(InvoiceProductGroup $item)
    {
        $tmpItem = [];
        $tmpItem['id'] = $item->getId();
        $tmpItem['title'] = $item->getTitle();
        $tmpItem['telephone'] = $item->getTitle();

        $tmpServices = [];
        $tmpRabates = [];

        $services = $item->getProducts();
        if ($services) {
            /** @var InvoiceProduct $service */
            foreach ($services as $service) {
                $custom = $service->getCustom();
                $data = [
                    'id' => $service->getId(),
                    'title' => $service->getTitle(),
                    'netValue' => $service->getNetValue(),
                    'vatPercentage' => $service->getVatPercentage(),
                    'priceValue' => $service->getPriceValue(),
                    'unit' => $service->getUnit(),
                    'quantity' => $service->getQuantity(),
                    'excise' => $service->getExcise(),
                ];

                $tmpServices[] = array_merge($data, $custom);
            }
        }
        $tmpItem['services'] = $tmpServices;

        $rabates = $item->getRabates();
        if ($rabates) {
            /** @var InvoiceProduct $rabate */
            foreach ($rabates as $rabate) {
                $tmpServices[] = [
                    'id' => $rabate->getId(),
                    'title' => $rabate->getTitle(),
                    'netValue' => $rabate->getNetValue(),
                    'vatPercentage' => $rabate->getVatPercentage(),
                ];
            }
        }
        $tmpItem['rabates'] = $tmpRabates;

        return $tmpItem;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $data = unserialize($this->data);

        if (!is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $result[] = $item;
            } else {
                $item = unserialize($item);

                $item['services'] = unserialize($item['services']);
                foreach ($item['services'] as $key => $service) {
                    if (is_array($service)) {
                        $item['services'][$key] = $service;
                        $data = $service;
                    } else {
                        $item['services'][$key] = unserialize($service);
                        $data = $item['services'][$key];
                    }
                    if ($item['services'][$key]['vatPercentage']) {
                        $netValue = $item['services'][$key]['netValue'];
                        try {
                            $item['services'][$key]['grossValue'] = $netValue + $netValue * ($item['services'][$key]['vatPercentage'] / 100);
                        } catch(\Exception $e) {
                            if(preg_match('/,/', $netValue)) {
                                $netValue = str_replace(',', '.', $netValue);
                            } else {
                                $netValue = str_replace('.', ',', $netValue);
                            }
                            $item['services'][$key]['grossValue'] = $netValue + $netValue * ($item['services'][$key]['vatPercentage'] / 100);
                        }
                        
                    } else {
                        $item['services'][$key]['grossValue'] = $data['netValue'];
                    }
                    $item['services'][$key]['grossValue'] = str_replace(',', '', number_format($item['services'][$key]['grossValue'], 2));
                }

                if (isset($item['rabates'])) {
                    $item['rabates'] = unserialize($item['rabates']);
                    foreach ($item['rabates'] as $key => $rabate) {
                        if (is_array($rabate)) {
                            $item['rabates'][$key] = $rabate;
                            $data = $rabate;
                        } else {
                            $item['rabates'][$key] = unserialize($rabate);
                            $data = $item['rabates'][$key];
                        }
                        if ($item['rabates'][$key]['vatPercentage']) {
                            $item['rabates'][$key]['grossValue'] = $item['rabates'][$key]['netValue'] + $item['rabates'][$key]['netValue'] * ($item['rabates'][$key]['vatPercentage'] / 100);
                        } else {
                            $item['rabates'][$key]['grossValue'] = $data['netValue'];
                        }
                        $item['rabates'][$key]['grossValue'] = str_replace(',', '', number_format($item['rabates'][$key]['grossValue'], 2));
                    }
                }

                $result[] = $item;
            }
        }

        return $result;
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
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getNumberStructure()
    {
        return $this->numberStructure;
    }

    /**
     * @param string $numberStructure
     */
    public function setNumberStructure($numberStructure)
    {
        $this->numberStructure = $numberStructure;
    }

    /**
     * @return string
     */
    public function getNumberLeadingZeros()
    {
        return $this->numberLeadingZeros;
    }

    /**
     * @param string $numberLeadingZeros
     */
    public function setNumberLeadingZeros($numberLeadingZeros)
    {
        $this->numberLeadingZeros = $numberLeadingZeros;
    }

    /**
     * @return string
     */
    public function getNumberResetAiAtNewMonth()
    {
        return $this->numberResetAiAtNewMonth;
    }

    /**
     * @param string $numberResetAiAtNewMonth
     */
    public function setNumberResetAiAtNewMonth($numberResetAiAtNewMonth)
    {
        $this->numberResetAiAtNewMonth = $numberResetAiAtNewMonth;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @param \DateTime $invoiceCreatedDate
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    /**
     * Set isPaid
     *
     * @param bool $isPaid
     *
     * @return InvoiceInterface
     */
    public function setIsPaid($isPaid)
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    /**
     * Get isPaid
     *
     * @return boolean
     */
    public function getIsPaid()
    {
        return $this->isPaid;
    }

    /**
     * Set dateOfPayment
     *
     * @param \DateTime $dateOfPayment
     *
     * @return InvoiceInterface
     */
    public function setDateOfPayment($dateOfPayment)
    {
        $this->dateOfPayment = $dateOfPayment;

        return $this;
    }

    /**
     * Get dateOfPayment
     *
     * @return \DateTime
     */
    public function getDateOfPayment()
    {
        return $this->dateOfPayment;
    }

    /**
     * @return string
     */
    public function getCreatedIn()
    {
        return $this->createdIn;
    }

    /**
     * @param string $createdIn
     */
    public function setCreatedIn($createdIn)
    {
        $this->createdIn = $createdIn;

        return $this;
    }

    /**
     * @return int
     */
    public function getBillingPeriod()
    {
        return $this->billingPeriod;
    }

    /**
     * @param int $billingPeriod
     */
    public function setBillingPeriod($billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getBillingPeriodFrom()
    {
        return $this->billingPeriodFrom;
    }

    /**
     * @param \DateTime $billingPeriodFrom
     */
    public function setBillingPeriodFrom($billingPeriodFrom)
    {
        $this->billingPeriodFrom = $billingPeriodFrom;
    }

    /**
     * @return \DateTime
     */
    public function getBillingPeriodTo()
    {
        return $this->billingPeriodTo;
    }

    /**
     * @param \DateTime $billingPeriodTo
     */
    public function setBillingPeriodTo($billingPeriodTo)
    {
        $this->billingPeriodTo = $billingPeriodTo;
    }

    public function getOverdueDateOfPayment()
    {
        if ($this->isPaid) {
            return 0;
        }

        $dateStart = $this->getDateOfPayment();
        $dateStart = $dateStart->setTime(0,0);
//        $dateStart = $dateStart->modify('+14 days');
        $dateEnd = new \DateTime('now');

        if ($dateStart < $dateEnd) {
            $diff = $dateStart->diff($dateEnd);
            $diffDays = $diff->days;
        } else {
            $diffDays = 0;
        }

        return $diffDays;
    }

    /**
     * Set clientNip
     *
     * @param string $clientNip
     *
     * @return InvoiceInterface
     */
    public function setClientNip($clientNip)
    {
        $this->clientNip = $clientNip;

        return $this;
    }

    /**
     * Get clientNip
     *
     * @return string
     */
    public function getClientNip()
    {
        return $this->clientNip;
    }

    /**
     * @return string
     */
    public function getClientPesel()
    {
        return $this->clientPesel;
    }

    /**
     * @param string $clientPesel
     */
    public function setClientPesel($clientPesel)
    {
        $this->clientPesel = $clientPesel;
    }

    /**
     * Set clientFullName
     *
     * @param string $clientFullName
     *
     * @return InvoiceInterface
     */
    public function setClientFullName($clientFullName)
    {
        $this->clientFullName = $clientFullName;

        return $this;
    }

    /**
     * Get clientFullName
     *
     * @return string
     */
    public function getClientFullName()
    {
        return $this->clientFullName;
    }

    /**
     * @return string
     */
    public function getClientStreet()
    {
        return $this->clientStreet;
    }

    /**
     * @param string $clientStreet
     */
    public function setClientStreet($clientStreet)
    {
        $this->clientStreet = $clientStreet;
    }

    /**
     * @return string
     */
    public function getClientHouseNr()
    {
        return $this->clientHouseNr;
    }

    /**
     * @param string $clientHouseNr
     */
    public function setClientHouseNr($clientHouseNr)
    {
        $this->clientHouseNr = $clientHouseNr;
    }

    /**
     * @return string
     */
    public function getClientApartmentNr()
    {
        return $this->clientApartmentNr;
    }

    /**
     * @param string $clientApartmentNr
     */
    public function setClientApartmentNr($clientApartmentNr)
    {
        $this->clientApartmentNr = $clientApartmentNr;
    }

    /**
     * Set clientZipCode
     *
     * @param string $clientZipCode
     *
     * @return InvoiceInterface
     */
    public function setClientZipCode($clientZipCode)
    {
        $this->clientZipCode = $clientZipCode;

        return $this;
    }

    /**
     * Get clientZipCode
     *
     * @return string
     */
    public function getClientZipCode()
    {
        return $this->clientZipCode;
    }

    /**
     * Set clientCity
     *
     * @param string $clientCity
     *
     * @return InvoiceInterface
     */
    public function setClientCity($clientCity)
    {
        $this->clientCity = $clientCity;

        return $this;
    }

    /**
     * Get clientCity
     *
     * @return string
     */
    public function getClientCity()
    {
        return $this->clientCity;
    }

    /**
     * Set summaryNetValue
     *
     * @param string $summaryNetValue
     *
     * @return InvoiceInterface
     */
    public function setSummaryNetValue($summaryNetValue)
    {
        $this->summaryNetValue = $summaryNetValue;

        return $this;
    }

    /**
     * Get summaryNetValue
     *
     * @return string
     */
    public function getSummaryNetValue()
    {
        return str_replace(',', '', number_format($this->summaryNetValue, 2));
    }

    /**
     * Set summaryGrossValue
     *
     * @param string $summaryGrossValue
     *
     * @return InvoiceInterface
     */
    public function setSummaryGrossValue($summaryGrossValue)
    {
        $this->summaryGrossValue = $summaryGrossValue;

        return $this;
    }

    /**
     * Get summaryGrossValue
     *
     * @return string
     */
    public function getSummaryGrossValue()
    {
        return str_replace(',', '', number_format($this->summaryGrossValue, 2));
    }

    /**
     * Set summaryVatValue
     *
     * @param string $summaryVatValue
     *
     * @return InvoiceInterface
     */
    public function setSummaryVatValue($summaryVatValue)
    {
        $this->summaryVatValue = $summaryVatValue;

        return $this;
    }

    /**
     * Get summaryVatValue
     *
     * @return string
     */
    public function getSummaryVatValue()
    {
        return str_replace(',', '', number_format($this->summaryVatValue, 2));
    }

    /**
     * Set balanceBeforeInvoice
     *
     * @param string $balanceBeforeInvoice
     *
     * @return InvoiceInterface
     */
    public function setBalanceBeforeInvoice($balanceBeforeInvoice)
    {
        if ($balanceBeforeInvoice) {
            $balanceBeforeInvoice = str_replace(',', '', $balanceBeforeInvoice);
        }

        if (is_numeric($balanceBeforeInvoice)) {
            $balanceBeforeInvoice = number_format($balanceBeforeInvoice, 2, '.', '');
        } else {
            $balanceBeforeInvoice = 0;
        }

        $this->balanceBeforeInvoice = $balanceBeforeInvoice;

        return $this;
    }

    /**
     * Get balanceBeforeInvoice
     *
     * @return string
     */
    public function getBalanceBeforeInvoice()
    {
        return $this->balanceBeforeInvoice;
    }

    /**
     * Set balanceAfterInvoice
     *
     * @param string $balanceAfterInvoice
     *
     * @return InvoiceInterface
     */
    public function setBalanceAfterInvoice($balanceAfterInvoice)
    {
        $this->balanceAfterInvoice = $balanceAfterInvoice;

        return $this;
    }

    /**
     * Get balanceAfterInvoice
     *
     * @return string
     */
    public function getBalanceAfterInvoice()
    {
        return $this->balanceAfterInvoice;
    }

    /**
     * @return mixed
     */
    public function getIsGeneratedFileExist()
    {
        return $this->isGeneratedFileExist;
    }

    /**
     * @param mixed $isGeneratedFileExist
     */
    public function setIsGeneratedFileExist($isGeneratedFileExist)
    {
        $this->isGeneratedFileExist = $isGeneratedFileExist;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return InvoiceInterface
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return InvoiceInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function __toString()
    {
        return $this->number;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTime());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());

            if ($this->getPaidValue() == null) {
                $this->setPaidValue(0);
            }
        }
    }

    public function __clone() {
        $this->id = null;
    }



    //
    // gtu codes
    //

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_1", type="boolean", nullable=true)
     */
    protected $gtu1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_2", type="boolean", nullable=true)
     */
    protected $gtu2;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_3", type="boolean", nullable=true)
     */
    protected $gtu3;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_4", type="boolean", nullable=true)
     */
    protected $gtu4;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_5", type="boolean", nullable=true)
     */
    protected $gtu5;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_6", type="boolean", nullable=true)
     */
    protected $gtu6;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_7", type="boolean", nullable=true)
     */
    protected $gtu7;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_8", type="boolean", nullable=true)
     */
    protected $gtu8;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_9", type="boolean", nullable=true)
     */
    protected $gtu9;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_10", type="boolean", nullable=true)
     */
    protected $gtu10;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_11", type="boolean", nullable=true)
     */
    protected $gtu11;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_12", type="boolean", nullable=true)
     */
    protected $gtu12;

    /**
     * @var boolean
     *
     * @ORM\Column(name="gtu_13", type="boolean", nullable=true)
     */
    protected $gtu13;

    /**
     * @return string
     */
    public function getGtu1()
    {
        return $this->gtu1;
    }

    /**
     * @param string $gtu1
     */
    public function setGtu1($code)
    {
        $this->gtu1 = $code;
    }

    /**
     * @return string
     */
    public function getGtu2()
    {
        return $this->gtu2;
    }

    /**
     * @param string $gtu2
     */
    public function setGtu2($code)
    {
        $this->gtu2 = $code;
    }

    /**
     * @return string
     */
    public function getGtu3()
    {
        return $this->gtu3;
    }

    /**
     * @param string $gtu3
     */
    public function setGtu3($code)
    {
        $this->gtu3 = $code;
    }

    /**
     * @return string
     */
    public function getGtu4()
    {
        return $this->gtu4;
    }

    /**
     * @param string $gtu4
     */
    public function setGtu4($code)
    {
        $this->gtu4 = $code;
    }

    /**
     * @return string
     */
    public function getGtu5()
    {
        return $this->gtu5;
    }

    /**
     * @param string $gtu5
     */
    public function setGtu5($code)
    {
        $this->gtu5 = $code;
    }

    /**
     * @return string
     */
    public function getGtu6()
    {
        return $this->gtu6;
    }

    /**
     * @param string $gtu6
     */
    public function setGtu6($code)
    {
        $this->gtu6 = $code;
    }

    /**
     * @return string
     */
    public function getGtu7()
    {
        return $this->gtu7;
    }

    /**
     * @param string $gtu7
     */
    public function setGtu7($code)
    {
        $this->gtu7 = $code;
    }

    /**
     * @return string
     */
    public function getGtu8()
    {
        return $this->gtu8;
    }

    /**
     * @param string $gtu8
     */
    public function setGtu8($code)
    {
        $this->gtu8 = $code;
    }

    /**
     * @return string
     */
    public function getGtu9()
    {
        return $this->gtu9;
    }

    /**
     * @param string $gtu9
     */
    public function setGtu9($code)
    {
        $this->gtu9 = $code;
    }

    /**
     * @return string
     */
    public function getGtu10()
    {
        return $this->gtu10;
    }

    /**
     * @param string $gtu10
     */
    public function setGtu10($code)
    {
        $this->gtu10 = $code;
    }

    /**
     * @return string
     */
    public function getGtu11()
    {
        return $this->gtu11;
    }

    /**
     * @param string $gtu11
     */
    public function setGtu11($code)
    {
        $this->gtu11 = $code;
    }

    /**
     * @return string
     */
    public function getGtu12()
    {
        return $this->gtu12;
    }

    /**
     * @param string $gtu12
     */
    public function setGtu12($code)
    {
        $this->gtu12 = $code;
    }

    /**
     * @return string
     */
    public function getGtu13()
    {
        return $this->gtu13;
    }

    /**
     * @param string $gtu13
     */
    public function setGtu13($code)
    {
        $this->gtu13 = $code;
    }

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_1", type="boolean", nullable=true)
     */
    protected $transactionProcedure1;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_2", type="boolean", nullable=true)
     */
    protected $transactionProcedure2;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_3", type="boolean", nullable=true)
     */
    protected $transactionProcedure3;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_4", type="boolean", nullable=true)
     */
    protected $transactionProcedure4;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_5", type="boolean", nullable=true)
     */
    protected $transactionProcedure5;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_6", type="boolean", nullable=true)
     */
    protected $transactionProcedure6;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_7", type="boolean", nullable=true)
     */
    protected $transactionProcedure7;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_8", type="boolean", nullable=true)
     */
    protected $transactionProcedure8;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_9", type="boolean", nullable=true)
     */
    protected $transactionProcedure9;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_10", type="boolean", nullable=true)
     */
    protected $transactionProcedure10;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_11", type="boolean", nullable=true)
     */
    protected $transactionProcedure11;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_12", type="boolean", nullable=true)
     */
    protected $transactionProcedure12;

    /**
     * @var boolean
     *
     * @ORM\Column(name="transaction_procedure_13", type="boolean", nullable=true)
     */
    protected $transactionProcedure13;

    /**
     * @return bool
     */
    public function getTransactionProcedure1()
    {
        return $this->transactionProcedure1;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure1($code)
    {
        $this->transactionProcedure1 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure2()
    {
        return $this->transactionProcedure2;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure2($code)
    {
        $this->transactionProcedure2 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure3()
    {
        return $this->transactionProcedure3;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure3($code)
    {
        $this->transactionProcedure3 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure4()
    {
        return $this->transactionProcedure4;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure4($code)
    {
        $this->transactionProcedure4 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure5()
    {
        return $this->transactionProcedure5;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure5($code)
    {
        $this->transactionProcedure5 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure6()
    {
        return $this->transactionProcedure6;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure6($code)
    {
        $this->transactionProcedure6 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure7()
    {
        return $this->transactionProcedure7;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure7($code)
    {
        $this->transactionProcedure7 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure8()
    {
        return $this->transactionProcedure8;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure8($code)
    {
        $this->transactionProcedure8 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure9()
    {
        return $this->transactionProcedure9;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure9($code)
    {
        $this->transactionProcedure9 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure10()
    {
        return $this->transactionProcedure10;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure10($code)
    {
        $this->transactionProcedure10 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure11()
    {
        return $this->transactionProcedure11;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure11($code)
    {
        $this->transactionProcedure11 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure12()
    {
        return $this->transactionProcedure12;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure12($code)
    {
        $this->transactionProcedure12 = $code;
    }

    /**
     * @return bool
     */
    public function getTransactionProcedure13()
    {
        return $this->transactionProcedure13;
    }

    /**
     * @param bool $code
     */
    public function setTransactionProcedure13($code)
    {
        $this->transactionProcedure13 = $code;
    }
}

