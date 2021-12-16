<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DebitNote
 *
 * @ORM\Table(name="debit_note")
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class DebitNote implements BillingDocumentInterface, IsDocumentReadyForBankAccountChangeInterface
{
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

    public function getOverdueDateOfPayment()
    {
        if ($this->isPaid) {
            return 0;
        }

        $dateStart = $this->getDateOfPayment();
        $dateStart = $dateStart->setTime(0,0);
        $dateEnd = new \DateTime('now');

        if ($dateStart < $dateEnd) {
            $diff = $dateStart->diff($dateEnd);
            $diffDays = $diff->days;
        } else {
            $diffDays = 0;
        }

        return $diffDays;
    }

    protected $isGeneratedFileExist;

    protected $isNotActual;

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
     * @var array
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param array $content
     */
    public function setContent($content)
    {
        $this->content = $content;
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
     * @ORM\ManyToOne(targetEntity="Wecoders\InvoiceBundle\Entity\InvoiceTemplate")
     * @ORM\JoinColumn(name="document_template_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $documentTemplate;

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
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $client;

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
     * @var \DateTime
     *
     * @ORM\Column(name="date_of_payment", type="date")
     */
    protected $dateOfPayment;

    /**
     * @return \DateTime
     */
    public function getDateOfPayment()
    {
        return $this->dateOfPayment;
    }

    /**
     * @param \DateTime $dateOfPayment
     */
    public function setDateOfPayment($dateOfPayment)
    {
        $this->dateOfPayment = $dateOfPayment;
    }

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
     * @var boolean
     *
     * @ORM\Column(name="is_paid", type="boolean", nullable=true)
     */
    protected $isPaid;

    /**
     * @return bool
     */
    public function getIsPaid()
    {
        return $this->isPaid;
    }

    /**
     * @param bool $isPaid
     */
    public function setIsPaid($isPaid)
    {
        $this->isPaid = $isPaid;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="date")
     */
    protected $createdDate;

    /**
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @param \DateTime $createdDate
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
    }

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

//    /**
//     * @var string
//     *
//     * @ORM\Column(name="client_nip", type="string", length=255, nullable=true)
//     */
//    protected $clientNip;
//
//    /**
//     * @var string
//     *
//     * @ORM\Column(name="client_pesel", type="string", length=255, nullable=true)
//     */
//    protected $clientPesel;

    /**
     * @var string
     *
     * @ORM\Column(name="client_name", type="string", length=255, nullable=true)
     */
    protected $clientName;

    /**
     * @var string
     *
     * @ORM\Column(name="client_surname", type="string", length=255, nullable=true)
     */
    protected $clientSurname;

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
     * @ORM\Column(name="client_post_office", type="string", length=255, nullable=true)
     */
    protected $clientPostOffice;

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
     * @return string
     */
    public function getClientZipCode()
    {
        return $this->clientZipCode;
    }

    /**
     * @param string $clientZipCode
     */
    public function setClientZipCode($clientZipCode)
    {
        $this->clientZipCode = $clientZipCode;
    }

    /**
     * @return string
     */
    public function getClientCity()
    {
        return $this->clientCity;
    }

    /**
     * @param string $clientCity
     */
    public function setClientCity($clientCity)
    {
        $this->clientCity = $clientCity;
    }

    /**
     * @return string
     */
    public function getClientPostOffice()
    {
        return $this->clientPostOffice;
    }

    /**
     * @param string $clientPostOffice
     */
    public function setClientPostOffice($clientPostOffice)
    {
        $this->clientPostOffice = $clientPostOffice;
    }

//    /**
//     * Set clientNip
//     *
//     * @param string $clientNip
//     *
//     * @return DebitNote
//     */
//    public function setClientNip($clientNip)
//    {
//        $this->clientNip = $clientNip;
//
//        return $this;
//    }
//
//    /**
//     * Get clientNip
//     *
//     * @return string
//     */
//    public function getClientNip()
//    {
//        return $this->clientNip;
//    }

//    /**
//     * @return string
//     */
//    public function getClientPesel()
//    {
//        return $this->clientPesel;
//    }
//
//    /**
//     * @param string $clientPesel
//     */
//    public function setClientPesel($clientPesel)
//    {
//        $this->clientPesel = $clientPesel;
//    }

    /**
     * @return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * @param string $clientName
     */
    public function setClientName($clientName)
    {
        $this->clientName = $clientName;
    }

    /**
     * @return string
     */
    public function getClientSurname()
    {
        return $this->clientSurname;
    }

    /**
     * @param string $clientSurname
     */
    public function setClientSurname($clientSurname)
    {
        $this->clientSurname = $clientSurname;
    }

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

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
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

    public function __toString()
    {
        return (string) $this->id;
    }

    public function getNumber()
    {
        return $this->id;
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
     * @var boolean
     *
     * @ORM\Column(name="paid_value", type="decimal", precision=10, scale=2)
     */
    protected $paidValue = 0;

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
}
