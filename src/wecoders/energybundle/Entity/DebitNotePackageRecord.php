<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DebitNotePackageRecord
 *
 * @ORM\Table(name="debit_note_package_record")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\PackageToProcessRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DebitNotePackageRecord
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\DebitNotePackage", inversedBy="packageRecords")
     * @ORM\JoinColumn(name="package_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $package;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $client;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\InvoiceBundle\Entity\InvoiceTemplate")
     * @ORM\JoinColumn(name="document_template_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $documentTemplate;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\Brand")
     * @ORM\JoinColumn(name="brand_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $brand;

    /**
     * @var string
     *
     * @ORM\Column(name="months_number", type="integer", nullable=true)
     */
    protected $monthsNumber;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="contract_sign_date", type="date", nullable=true)
     */
    protected $contractSignDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="contract_from_date", type="date", nullable=true)
     */
    protected $contractFromDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="contract_to_date", type="date", nullable=true)
     */
    protected $contractToDate;

    /**
     * @var string
     *
     * @ORM\Column(name="penalty_amount_per_month", type="string", length=255, nullable=true)
     */
    protected $penaltyAmountPerMonth;

    /**
     * @var string
     *
     * @ORM\Column(name="summary_gross_value", type="decimal", precision=10, scale=2, nullable=true)
     */
    protected $summaryGrossValue;

    /**
     * @return string
     */
    public function getSummaryGrossValue()
    {
        return $this->summaryGrossValue;
    }

    /**
     * @param string $summaryGrossValue
     */
    public function setSummaryGrossValue($summaryGrossValue)
    {
        $this->summaryGrossValue = $summaryGrossValue;
    }

    /**
     * @return string
     */
    public function getMonthsNumber()
    {
        return $this->monthsNumber;
    }

    /**
     * @param string $monthsNumber
     */
    public function setMonthsNumber($monthsNumber)
    {
        $this->monthsNumber = $monthsNumber;
    }

    /**
     * @return \DateTime
     */
    public function getContractSignDate()
    {
        return $this->contractSignDate;
    }

    /**
     * @param \DateTime $contractSignDate
     */
    public function setContractSignDate($contractSignDate)
    {
        $this->contractSignDate = $contractSignDate;
    }

    /**
     * @return \DateTime
     */
    public function getContractFromDate()
    {
        return $this->contractFromDate;
    }

    /**
     * @param \DateTime $contractFromDate
     */
    public function setContractFromDate($contractFromDate)
    {
        $this->contractFromDate = $contractFromDate;
    }

    /**
     * @return \DateTime
     */
    public function getContractToDate()
    {
        return $this->contractToDate;
    }

    /**
     * @param \DateTime $contractToDate
     */
    public function setContractToDate($contractToDate)
    {
        $this->contractToDate = $contractToDate;
    }

    /**
     * @return string
     */
    public function getPenaltyAmountPerMonth()
    {
        return $this->penaltyAmountPerMonth;
    }

    /**
     * @param string $penaltyAmountPerMonth
     */
    public function setPenaltyAmountPerMonth($penaltyAmountPerMonth)
    {
        $this->penaltyAmountPerMonth = $penaltyAmountPerMonth;
    }

    /**
     * @return mixed
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param mixed $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="contract_number", type="string", length=100)
     */
    private $contractNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="contract_type", type="string", length=10)
     */
    private $contractType;

    /**
     * @return mixed
     */
    public function getDocumentTemplate()
    {
        return $this->documentTemplate;
    }

    /**
     * @param mixed $documentTemplate
     */
    public function setDocumentTemplate($documentTemplate)
    {
        $this->documentTemplate = $documentTemplate;
    }

    /**
     * @return mixed
     */
    public function getContractNumber()
    {
        return $this->contractNumber;
    }

    /**
     * @param mixed $contractNumber
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;
    }

    /**
     * @return string
     */
    public function getContractType()
    {
        return $this->contractType;
    }

    /**
     * @param string $contractType
     */
    public function setContractType($contractType)
    {
        $this->contractType = $contractType;
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
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param mixed $package
     */
    public function setPackage($package)
    {
        $this->package = $package;
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
     * @ORM\Column(name="account_number_identifier", type="string", length=12)
     */
    private $accountNumberIdentifier;

    /**
     * @return string
     */
    public function getAccountNumberIdentifier()
    {
        return $this->accountNumberIdentifier;
    }

    /**
     * @param string $accountNumberIdentifier
     */
    public function setAccountNumberIdentifier($accountNumberIdentifier)
    {
        $this->accountNumberIdentifier = $accountNumberIdentifier;
    }

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
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error_message", type="text", nullable=true)
     */
    private $errorMessage;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\DebitNote", cascade={"remove"})
     * @ORM\JoinColumn(name="document_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $document;

    /**
     * @return mixed
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param mixed $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return DebitNotePackageRecord
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
     * @return DebitNotePackageRecord
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
        $this->setUpdatedAt(new \DateTime());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());
        }
    }
}
