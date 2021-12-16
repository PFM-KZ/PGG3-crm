<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;

/**
 * InvoiceProforma
 *
 * @ORM\Table(name="invoice_proforma")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class InvoiceProforma implements InvoiceInterface
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
     * @var string
     *
     * @ORM\Column(name="number", type="string", length=255, unique=true)
     */
    private $number;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings")
     * @ORM\JoinColumn(name="invoice_number_settings_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $invoiceNumberSettings;

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
    private $numberStructure;

    /**
     * @var string
     *
     * @ORM\Column(name="number_leading_zeros", type="boolean")
     */
    private $numberLeadingZeros;

    /**
     * @var string
     *
     * @ORM\Column(name="number_reset_ai_at_new_month", type="boolean")
     */
    private $numberResetAiAtNewMonth;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="date")
     */
    private $createdDate;

    /**
     * @var string
     *
     * @ORM\Column(name="created_in", type="string", length=255, nullable=true)
     */
    private $createdIn;

    /**
     * @var integer
     *
     * @ORM\Column(name="billing_period", type="integer", length=6, nullable=true)
     */
    private $billingPeriod;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_payment", type="date")
     */
    private $dateOfPayment;


    /**
     * @var string
     *
     * @ORM\Column(name="seller_title", type="string", length=255, nullable=true)
     */
    private $sellerTitle;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_address", type="string", length=255, nullable=true)
     */
    private $sellerAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_zip_code", type="string", length=255, nullable=true)
     */
    private $sellerZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_city", type="string", length=255, nullable=true)
     */
    private $sellerCity;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_nip", type="string", length=255, nullable=true)
     */
    private $sellerNip;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_bank_name", type="string", length=255, nullable=true)
     */
    private $sellerBankName;

    /**
     * @var string
     *
     * @ORM\Column(name="seller_bank_account", type="string", length=255, nullable=true)
     */
    private $sellerBankAccount;


    /**
     * @var string
     *
     * @ORM\Column(name="client_nip", type="string", length=255, nullable=true)
     */
    private $clientNip;

    /**
     * @var string
     *
     * @ORM\Column(name="client_full_name", type="string", length=255, nullable=true)
     */
    private $clientFullName;

    /**
     * @var string
     *
     * @ORM\Column(name="client_address", type="string", length=255, nullable=true)
     */
    private $clientAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="client_zip_code", type="string", length=255, nullable=true)
     */
    private $clientZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="client_city", type="string", length=255, nullable=true)
     */
    private $clientCity;


    /**
     * @var array
     *
     * @ORM\Column(name="data", type="text", nullable=true)
     */
    private $data;


    /**
     * @var string
     *
     * @ORM\Column(name="summary_net_value", type="string", length=255, nullable=true)
     */
    private $summaryNetValue;

    /**
     * @var string
     *
     * @ORM\Column(name="summary_gross_value", type="string", length=255, nullable=true)
     */
    private $summaryGrossValue;

    /**
     * @var string
     *
     * @ORM\Column(name="summary_vat_value", type="string", length=255, nullable=true)
     */
    private $summaryVatValue;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;




    private $calculatedSummaryGrossValue;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_paid", type="boolean")
     */
    private $isPaid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="paid_value", type="decimal", precision=10, scale=2)
     */
    protected $paidValue;

    /**
     * @return bool
     */
    public function getPaidValue()
    {
        return $this->paidValue;
    }

    /**
     * @param bool $paidValue
     */
    public function setPaidValue($paidValue)
    {
        $this->paidValue = $paidValue;
    }

    private $overdueDateOfPayment;

    private $isGeneratedFileExist;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $seller;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_electronic", type="boolean", options={"default": 0})
     */
    private $isElectronic;

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

    /**
     * Set data
     *
     * @param array data
     *
     * @return InvoiceProforma
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

            $rabates = [];
            foreach ($item['rabates'] as $rabate) {
                unset($rabate['grossValue']);
                $rabates[] = serialize($rabate);
            }
            $item['rabates'] = serialize($rabates);

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
                $tmpServices[] = [
                    'id' => $service->getId(),
                    'title' => $service->getTitle(),
                    'netValue' => $service->getNetValue(),
                    'vatPercentage' => $service->getVatPercentage(),
                ];
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
                        $item['services'][$key]['grossValue'] = $item['services'][$key]['netValue'] + $item['services'][$key]['netValue'] * ($item['services'][$key]['vatPercentage'] / 100);
                    } else {
                        $item['services'][$key]['grossValue'] = $data['netValue'];
                    }
                    $item['services'][$key]['grossValue'] = str_replace(',', '', number_format($item['services'][$key]['grossValue'], 2));
                }

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
     * Set isPaid
     *
     * @param bool $isPaid
     *
     * @return InvoiceProforma
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
     * @return InvoiceProforma
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

//    public function getOverdueDateOfPayment()
//    {
//        if ($this->isPaid) {
//            return 0;
//        }
//
//        if ($this->getBillingPeriod()) {
//            $billingPeriod = $this->getBillingPeriod(); // format YYYYMM
//            $year = substr($billingPeriod, 0, 4);
//            $month = substr($billingPeriod, 4, 2);
//            $dateStart = new \DateTime();
//            $dateStart = $dateStart->setDate($year, $month, 1);
//            $dateStart = $dateStart->setTime(0, 0, 0);
//            $dateStart = $dateStart->modify('+13 days');
//        } else {
//            $dateStart = $this->getCreatedDate();
//            $dateStart = $dateStart->setTime(0,0);
//            $dateStart = $dateStart->modify('+14 days');
//        }
//
//        $dateEnd = new \DateTime('now');
//
//        if ($dateStart < $dateEnd) {
//            $diff = $dateStart->diff($dateEnd);
//            $diffDays = $diff->days;
//        } else {
//            $diffDays = 0;
//        }
//
//        return $diffDays;
//    }

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
     * @return InvoiceProforma
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
     * Set clientFullName
     *
     * @param string $clientFullName
     *
     * @return InvoiceProforma
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
     * Set clientAddress
     *
     * @param string $clientAddress
     *
     * @return InvoiceProforma
     */
    public function setClientAddress($clientAddress)
    {
        $this->clientAddress = $clientAddress;

        return $this;
    }

    /**
     * Get clientAddress
     *
     * @return string
     */
    public function getClientAddress()
    {
        return $this->clientAddress;
    }

    /**
     * Set clientZipCode
     *
     * @param string $clientZipCode
     *
     * @return InvoiceProforma
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
     * @return InvoiceProforma
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
     * @return InvoiceProforma
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
     * @return InvoiceProforma
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
     * @return InvoiceProforma
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
     * @return InvoiceProforma
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
     * @return InvoiceProforma
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
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }

        if ($this->getPaidValue() == null) {
            $this->setPaidValue(0);
        }
    }
}

