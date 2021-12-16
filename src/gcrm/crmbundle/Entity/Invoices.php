<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Invoices
 *
 * @ORM\Table(name="invoices")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\InvoicesRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Invoices
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Company")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $company;

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @ORM\OneToOne(targetEntity="Invoices")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $invoice;

    public function getInvoice()
    {
        return $this->invoice;
    }

    public function setInvoice(Invoices $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\InvoiceAndRabate", mappedBy="invoice", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $invoiceAndRabates;

    public function addInvoiceAndRabate(InvoiceAndRabate $invoiceAndRabate)
    {
        $this->invoiceAndRabates[] = $invoiceAndRabate;
        $invoiceAndRabate->setInvoice($this);

        return $this;
    }

    public function removeInvoiceAndRabate(InvoiceAndRabate $invoiceAndRabate)
    {
        $this->invoiceAndRabates->removeElement($invoiceAndRabate);
    }

    public function getInvoiceAndRabates()
    {
        return $this->invoiceAndRabates;
    }

    public function setInvoiceAndRabates($invoiceAndRabates)
    {
        $this->invoiceAndRabates = $invoiceAndRabates;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="crm_nr", type="string", length=50)
     */
    private $crmNr;

    /**
     * @var string
     *
     * @ORM\Column(name="correction_nr", type="string", length=50, nullable=true)
     */
    private $correctionNr;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="crm_invoice_created_date", type="datetime")
     */
    private $crmInvoiceCreatedDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_paid", type="boolean")
     */
    private $isPaid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_correction", type="boolean", nullable=true)
     */
    private $isCorrection;

    /**
     * @var string
     *
     * @ORM\Column(name="nr", type="string", length=50)
     */
    private $nr;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_invoice", type="datetime")
     */
    private $dateOfInvoice;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_correction", type="datetime", nullable=true)
     */
    private $dateOfCorrection;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_payment", type="datetime")
     */
    private $dateOfPayment;

    private $overdueDateOfPayment;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="for_time_from", type="datetime")
     */
    private $forTimeFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="for_time_to", type="datetime")
     */
    private $forTimeTo;

    /**
     * @var string
     *
     * @ORM\Column(name="client_nip", type="string", length=255, nullable=true)
     */
    private $clientNip;

    /**
     * @var string
     *
     * @ORM\Column(name="client_nr", type="string", length=255)
     */
    private $clientNr;

//    /**
//     * @var string
//     *
//     * @ORM\Column(name="client_name", type="string", length=255)
//     */
//    private $clientName;

//    /**
//     * @var string
//     *
//     * @ORM\Column(name="client_surname", type="string", length=255)
//     */
//    private $clientSurname;

    /**
     * @var string
     *
     * @ORM\Column(name="client_full_name", type="string", length=255)
     */
    private $clientFullName;

    /**
     * @var string
     *
     * @ORM\Column(name="client_address", type="string", length=255)
     */
    private $clientAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="client_zipcode", type="string", length=255)
     */
    private $clientZipcode;

    /**
     * @var string
     *
     * @ORM\Column(name="client_city", type="string", length=255)
     */
    private $clientCity;

    /**
     * @var string
     *
     * @ORM\Column(name="connections_nr", type="string", length=255, nullable=true)
     */
    private $connectionsNr;

    /**
     * @var string
     *
     * @ORM\Column(name="connections_time", type="string", length=255, nullable=true)
     */
    private $connectionsTime;

    /**
     * @var string
     *
     * @ORM\Column(name="summary_net_value", type="string", length=255)
     */
    private $summaryNetValue;

    /**
     * @var string
     *
     * @ORM\Column(name="summary_gross_value", type="string", length=255)
     */
    private $summaryGrossValue;

    private $calculatedSummaryGrossValue;

    /**
     * @var string
     *
     * @ORM\Column(name="summary_vat_value", type="string", length=255)
     */
    private $summaryVatValue;

    /**
     * @var string
     *
     * @ORM\Column(name="balance_before_invoice", type="string", length=255, nullable=true)
     */
    private $balanceBeforeInvoice;

    /**
     * @var string
     *
     * @ORM\Column(name="balance_after_invoice", type="string", length=255, nullable=true)
     */
    private $balanceAfterInvoice;

    /**
     * @var array
     *
     * @ORM\Column(name="services", type="text", nullable=true)
     */
    private $services;

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
     * Set crmNr
     *
     * @param string $crmNr
     *
     * @return Invoices
     */
    public function setCrmNr($crmNr)
    {
        $this->crmNr = $crmNr;

        return $this;
    }

    /**
     * Get crmNr
     *
     * @return string
     */
    public function getCrmNr()
    {
        return $this->crmNr;
    }

    /**
     * Set correctionNr
     *
     * @param string $correctionNr
     *
     * @return Invoices
     */
    public function setCorrectionNr($correctionNr)
    {
        $this->correctionNr = $correctionNr;

        return $this;
    }

    /**
     * Get correctionNr
     *
     * @return string
     */
    public function getCorrectionNr()
    {
        return $this->correctionNr;
    }

    /**
     * Set crmInvoiceCreatedDate
     *
     * @param \DateTime $crmInvoiceCreatedDate
     *
     * @return Invoices
     */
    public function setCrmInvoiceCreatedDate($crmInvoiceCreatedDate)
    {
        $this->crmInvoiceCreatedDate = $crmInvoiceCreatedDate;

        return $this;
    }

    /**
     * Get crmInvoiceCreatedDate
     *
     * @return \DateTime
     */
    public function getCrmInvoiceCreatedDate()
    {
        return $this->crmInvoiceCreatedDate;
    }

    /**
     * Set nr
     *
     * @param string $nr
     *
     * @return Invoices
     */
    public function setNr($nr)
    {
        $this->nr = $nr;

        return $this;
    }

    /**
     * Get nr
     *
     * @return string
     */
    public function getNr()
    {
        return $this->nr;
    }

    /**
     * Set isPaid
     *
     * @param boolea $isPaid
     *
     * @return Invoices
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
     * Set isCorrection
     *
     * @param boolean $isCorrection
     *
     * @return Invoices
     */
    public function setIsCorrection($isCorrection)
    {
        $this->isCorrection = $isCorrection;

        return $this;
    }

    /**
     * Get isCorrection
     *
     * @return boolean
     */
    public function getIsCorrection()
    {
        return $this->isCorrection;
    }

    /**
     * Set dateOfInvoice
     *
     * @param \DateTime $dateOfInvoice
     *
     * @return Invoices
     */
    public function setDateOfInvoice($dateOfInvoice)
    {
        $this->dateOfInvoice = $dateOfInvoice;

        return $this;
    }

    /**
     * Get dateOfInvoice
     *
     * @return \DateTime
     */
    public function getDateOfInvoice()
    {
        return $this->dateOfInvoice;
    }

    /**
     * Set dateOfInvoice
     *
     * @param \DateTime $dateOfCorrection
     *
     * @return Invoices
     */
    public function setDateOfCorrection($dateOfCorrection)
    {
        $this->dateOfCorrection = $dateOfCorrection;

        return $this;
    }

    /**
     * Get dateOfCorrection
     *
     * @return \DateTime
     */
    public function getDateOfCorrection()
    {
        return $this->dateOfCorrection;
    }

    /**
     * Set dateOfPayment
     *
     * @param \DateTime $dateOfPayment
     *
     * @return Invoices
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

    public function getOverdueDateOfPayment()
    {
        if ($this->isPaid) {
            return 0;
        }

        $dateStart = $this->getCrmInvoiceCreatedDate();
        $dateStart = $dateStart->setTime(0,0);
        $dateStart = $dateStart->modify('+14 days');
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
     * Set forTimeFrom
     *
     * @param \DateTime $forTimeFrom
     *
     * @return Invoices
     */
    public function setForTimeFrom($forTimeFrom)
    {
        $this->forTimeFrom = $forTimeFrom;

        return $this;
    }

    /**
     * Get forTimeFrom
     *
     * @return \DateTime
     */
    public function getForTimeFrom()
    {
        return $this->forTimeFrom;
    }

    /**
     * Set forTimeTo
     *
     * @param \DateTime $forTimeTo
     *
     * @return Invoices
     */
    public function setForTimeTo($forTimeTo)
    {
        $this->forTimeTo = $forTimeTo;

        return $this;
    }

    /**
     * Get forTimeTo
     *
     * @return \DateTime
     */
    public function getForTimeTo()
    {
        return $this->forTimeTo;
    }

    /**
     * Set clientNip
     *
     * @param string $clientNip
     *
     * @return Invoices
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
     * Set clientNr
     *
     * @param string $clientNr
     *
     * @return Invoices
     */
    public function setClientNr($clientNr)
    {
        $this->clientNr = $clientNr;

        return $this;
    }

    /**
     * Get clientNr
     *
     * @return string
     */
    public function getClientNr()
    {
        return $this->clientNr;
    }

//    /**
//     * Set clientName
//     *
//     * @param string $clientName
//     *
//     * @return Invoices
//     */
//    public function setClientName($clientName)
//    {
//        $this->clientName = $clientName;
//
//        return $this;
//    }

//    /**
//     * Get clientName
//     *
//     * @return string
//     */
//    public function getClientName()
//    {
//        return $this->clientName;
//    }

//    /**
//     * Set clientSurname
//     *
//     * @param string $clientSurname
//     *
//     * @return Invoices
//     */
//    public function setClientSurname($clientSurname)
//    {
//        $this->clientSurname = $clientSurname;
//
//        return $this;
//    }

//    /**
//     * Get clientSurname
//     *
//     * @return string
//     */
//    public function getClientSurname()
//    {
//        return $this->clientSurname;
//    }

    /**
     * Set clientFullName
     *
     * @param string $clientFullName
     *
     * @return Invoices
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
     * @return Invoices
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
     * Set clientZipcode
     *
     * @param string $clientZipcode
     *
     * @return Invoices
     */
    public function setClientZipcode($clientZipcode)
    {
        $this->clientZipcode = $clientZipcode;

        return $this;
    }

    /**
     * Get clientZipcode
     *
     * @return string
     */
    public function getClientZipcode()
    {
        return $this->clientZipcode;
    }

    /**
     * Set clientCity
     *
     * @param string $clientCity
     *
     * @return Invoices
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
     * Set connectionsNr
     *
     * @param string $connectionsNr
     *
     * @return Invoices
     */
    public function setConnectionsNr($connectionsNr)
    {
        $this->connectionsNr = $connectionsNr;

        return $this;
    }

    /**
     * Get connectionsNr
     *
     * @return string
     */
    public function getConnectionsNr()
    {
        return $this->connectionsNr;
    }

    /**
     * Set connectionsTime
     *
     * @param string $connectionsTime
     *
     * @return Invoices
     */
    public function setConnectionsTime($connectionsTime)
    {
        $this->connectionsTime = $connectionsTime;

        return $this;
    }

    /**
     * Get connectionsTime
     *
     * @return string
     */
    public function getConnectionsTime()
    {
        return $this->connectionsTime;
    }

    /**
     * Set summaryNetValue
     *
     * @param string $summaryNetValue
     *
     * @return Invoices
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
        return $this->summaryNetValue;
    }

    /**
     * Set summaryGrossValue
     *
     * @param string $summaryGrossValue
     *
     * @return Invoices
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
        return $this->summaryGrossValue;
    }

    /**
     * Get calculatedSummaryGrossValue
     *
     * @return string
     */
    public function getCalculatedSummaryGrossValue()
    {
        $products = $this->getServices();

        $summaryNetValue = 0;
        $summaryVatValue = 0;

        foreach ($products as $product) {
            $product = unserialize($product);

            if (isset($product['rabate']) && $product['rabate']) {
                $summaryNetValue -= $product['netValue'];
            } else {
                $summaryNetValue += $product['netValue'];
                if ($product['vatPercentage']) {
                    $summaryVatValue += number_format($product['netValue'] * 0.23, 2);
                }
            }
        }

        $summaryNetValue = number_format(($summaryNetValue), 2);
        $this->calculatedSummaryGrossValue = number_format(($summaryNetValue + $summaryVatValue), 2);

        return $this->calculatedSummaryGrossValue;
    }

    /**
     * Set summaryVatValue
     *
     * @param string $summaryVatValue
     *
     * @return Invoices
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
        return $this->summaryVatValue;
    }

    /**
     * Set balanceBeforeInvoice
     *
     * @param string $balanceBeforeInvoice
     *
     * @return Invoices
     */
    public function setBalanceBeforeInvoice($balanceBeforeInvoice)
    {
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
     * @return Invoices
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
     * Set services
     *
     * @param array $services
     *
     * @return Invoices
     */
    public function setServices($services)
    {
        $result = [];
        foreach ($services as $key => $service) {
            $result[] = serialize($service);
        }

        $this->services = serialize($result);

        return $this;
    }

    /**
     * Get services
     *
     * @return array
     */
    public function getServices()
    {
        $services = unserialize($this->services);

        if (!is_array($services)) {
            return [];
        }

        $result = [];
        foreach ($services as $value) {
            if (is_array($value)) {
                $result[] = $value;
            } else {
                $result[] = unserialize($value);
            }
        }

        return $result;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Invoices
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
     * @return Invoices
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
    }
}

