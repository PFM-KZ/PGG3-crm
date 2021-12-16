<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Wecoders\EnergyBundle\Entity\ContractEnergyInterface;

/**
 * ContractEnergyBase
 */
abstract class ContractEnergyBase implements ContractInterface, ContractEnergyInterface
{
    abstract function getContractAndDistributionTariffs();
    abstract function getContractAndSellerTariffs();
    abstract function getPpCodeByDate($date);
    abstract function getSellerTariffByDate($date);
    abstract function getDistributionTariffByDate($date);

    public function getPpFullAddress()
    {
        $fullHouseOrApartmentNr = null;
        if ($this->getPpHouseNr() && $this->getPpApartmentNr()) {
            $fullHouseOrApartmentNr = $this->getPpHouseNr() . '/' . $this->getPpApartmentNr();
        } elseif ($this->getPpHouseNr()) {
            $fullHouseOrApartmentNr = $this->getPpHouseNr();
        }

        if (!$this->ppStreet || !$fullHouseOrApartmentNr || !$this->ppZipCode || !$this->ppCity) {
            return null;
        }

        return $this->ppStreet . ' ' . $fullHouseOrApartmentNr . ', ' . $this->ppZipCode . ' ' . $this->ppCity;
    }

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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusDepartment")
     * @ORM\JoinColumn(name="status_department_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusDepartment;

    public function getStatusDepartment()
    {
        return $this->statusDepartment;
    }

    public function setStatusDepartment($statusDepartment)
    {
        $this->statusDepartment = $statusDepartment;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
     * @ORM\JoinColumn(name="actual_status_contract_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $actualStatus;

    /**
     * @return mixed
     */
    public function getActualStatus()
    {
        return $this->actualStatus;
    }

    /**
     * @param mixed $actualStatus
     */
    public function setActualStatus($actualStatus)
    {
        $this->actualStatus = $actualStatus;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContractAuthorization")
     * @ORM\JoinColumn(name="status_authorization_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusAuthorization;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
     * @ORM\JoinColumn(name="status_contract_authorization_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusContractAuthorization;

    /**
     * @return mixed
     */
    public function getStatusContractAuthorization()
    {
        return $this->statusContractAuthorization;
    }

    /**
     * @param mixed $statusContractAuthorization
     */
    public function setStatusContractAuthorization($statusContractAuthorization)
    {
        $this->statusContractAuthorization = $statusContractAuthorization;
    }

    /**
     * @return mixed
     */
    public function getStatusAuthorization()
    {
        return $this->statusAuthorization;
    }

    /**
     * @param mixed $statusAuthorization
     */
    public function setStatusAuthorization($statusAuthorization)
    {
        $this->statusAuthorization = $statusAuthorization;

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="comment_authorization", type="text", nullable=true)
     */
    protected $commentAuthorization;

    /**
     * @return string
     */
    public function getCommentAuthorization()
    {
        return $this->commentAuthorization;
    }

    /**
     * @param string $commentAuthorization
     */
    public function setCommentAuthorization($commentAuthorization)
    {
        $this->commentAuthorization = $commentAuthorization;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContractVerification")
     * @ORM\JoinColumn(name="status_verification_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusVerification;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
     * @ORM\JoinColumn(name="status_contract_verification_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusContractVerification;

    /**
     * @return mixed
     */
    public function getStatusContractVerification()
    {
        return $this->statusContractVerification;
    }

    /**
     * @param mixed $statusContractVerification
     */
    public function setStatusContractVerification($statusContractVerification)
    {
        $this->statusContractVerification = $statusContractVerification;
    }

    public function getStatusVerification()
    {
        return $this->statusVerification;
    }

    public function setStatusVerification($statusVerification)
    {
        $this->statusVerification = $statusVerification;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="comment_verification", type="text", nullable=true)
     */
    protected $commentVerification;

    /**
     * @return string
     */
    public function getCommentVerification()
    {
        return $this->commentVerification;
    }

    /**
     * @param string $commentVerification
     */
    public function setCommentVerification($commentVerification)
    {
        $this->commentVerification = $commentVerification;

        return $this;
    }


    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContractAdministration")
     * @ORM\JoinColumn(name="status_administration_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusAdministration;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
     * @ORM\JoinColumn(name="status_contract_administration_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusContractAdministration;

    /**
     * @return mixed
     */
    public function getStatusContractAdministration()
    {
        return $this->statusContractAdministration;
    }

    /**
     * @param mixed $statusContractAdministration
     */
    public function setStatusContractAdministration($statusContractAdministration)
    {
        $this->statusContractAdministration = $statusContractAdministration;
    }

    /**
     * @return mixed
     */
    public function getStatusAdministration()
    {
        return $this->statusAdministration;
    }

    /**
     * @param mixed $statusAdministration
     */
    public function setStatusAdministration($statusAdministration)
    {
        $this->statusAdministration = $statusAdministration;

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="comment_administration", type="text", nullable=true)
     */
    protected $commentAdministration;

    /**
     * @return mixed
     */
    public function getCommentAdministration()
    {
        return $this->commentAdministration;
    }

    /**
     * @param mixed $commentAdministration
     */
    public function setCommentAdministration($commentAdministration)
    {
        $this->commentAdministration = $commentAdministration;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContractControl")
     * @ORM\JoinColumn(name="status_control_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusControl;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
     * @ORM\JoinColumn(name="status_contract_control_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusContractControl;

    /**
     * @return mixed
     */
    public function getStatusContractControl()
    {
        return $this->statusContractControl;
    }

    /**
     * @param mixed $statusContractControl
     */
    public function setStatusContractControl($statusContractControl)
    {
        $this->statusContractControl = $statusContractControl;
    }

    /**
     * @return mixed
     */
    public function getStatusControl()
    {
        return $this->statusControl;
    }

    /**
     * @param mixed $statusControl
     */
    public function setStatusControl($statusControl)
    {
        $this->statusControl = $statusControl;

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="comment_control", type="text", nullable=true)
     */
    protected $commentControl;

    /**
     * @return mixed
     */
    public function getCommentControl()
    {
        return $this->commentControl;
    }

    /**
     * @param mixed $commentControl
     */
    public function setCommentControl($commentControl)
    {
        $this->commentControl = $commentControl;

        return $this;
    }


    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContractProcess")
     * @ORM\JoinColumn(name="status_process_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusProcess;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
     * @ORM\JoinColumn(name="status_contract_process_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusContractProcess;

    /**
     * @return mixed
     */
    public function getStatusContractProcess()
    {
        return $this->statusContractProcess;
    }

    /**
     * @param mixed $statusContractProcess
     */
    public function setStatusContractProcess($statusContractProcess)
    {
        $this->statusContractProcess = $statusContractProcess;
    }

    /**
     * @return mixed
     */
    public function getStatusProcess()
    {
        return $this->statusProcess;
    }

    /**
     * @param mixed $statusProcess
     */
    public function setStatusProcess($statusProcess)
    {
        $this->statusProcess = $statusProcess;

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="comment_process", type="text", nullable=true)
     */
    protected $commentProcess;

    /**
     * @return string
     */
    public function getCommentProcess()
    {
        return $this->commentProcess;
    }

    /**
     * @param string $commentProcess
     */
    public function setCommentProcess($commentProcess)
    {
        $this->commentProcess = $commentProcess;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContractFinances")
     * @ORM\JoinColumn(name="status_finances_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusFinances;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
     * @ORM\JoinColumn(name="status_contract_finances_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $statusContractFinances;

    /**
     * @return mixed
     */
    public function getStatusContractFinances()
    {
        return $this->statusContractFinances;
    }

    /**
     * @param mixed $statusContractFinances
     */
    public function setStatusContractFinances($statusContractFinances)
    {
        $this->statusContractFinances = $statusContractFinances;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="comment_finances", type="text", nullable=true)
     */
    protected $commentFinances;

    /**
     * @return mixed
     */
    public function getStatusFinances()
    {
        return $this->statusFinances;
    }

    /**
     * @param mixed $statusFinances
     */
    public function setStatusFinances($statusFinances)
    {
        $this->statusFinances = $statusFinances;
    }

    /**
     * @return mixed
     */
    public function getCommentFinances()
    {
        return $this->commentFinances;
    }

    /**
     * @param mixed $commentFinances
     */
    public function setCommentFinances($commentFinances)
    {
        $this->commentFinances = $commentFinances;
    }


    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ContractEnergyAttachment", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $contractAttachments;

    public function addContractAttachment($contractAttachment)
    {
        $this->contractAttachments[] = $contractAttachment;
        $contractAttachment->setContract($this);

        return $this;
    }

    public function removeContractAttachment($contractAttachment)
    {
        $this->contractAttachments->removeElement($contractAttachment);
    }

    public function getContractAttachments()
    {
        return $this->contractAttachments;
    }

    public function setContractAttachments($contractAttachments)
    {
        $this->contractAttachments = $contractAttachments;
    }

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\RecordingEnergyAttachment", mappedBy="contract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $recordingAttachments;

    public function addRecordingAttachment($recordingAttachment)
    {
        $this->recordingAttachments[] = $recordingAttachment;
        $recordingAttachment->setContract($this);

        return $this;
    }

    public function removeRecordingAttachment($recordingAttachment)
    {
        $this->recordingAttachments->removeElement($recordingAttachment);
    }

    public function getRecordingAttachments()
    {
        return $this->recordingAttachments;
    }

    public function setRecordingAttachments($recordingAttachments)
    {
        $this->recordingAttachments = $recordingAttachments;
    }










    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\Brand")
     * @ORM\JoinColumn(name="brand_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $brand;

    public function getBrand()
    {
        return $this->brand;
    }

    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }



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

        return $this;
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

        return $this;
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
     * @ORM\Column(name="contract_number", type="string", length=255, nullable=true)
     */
    protected $contractNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="penalty_amount_per_month", type="string", length=255, nullable=true)
     */
    protected $penaltyAmountPerMonth;

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
     * @var \DateTime
     *
     * @ORM\Column(name="sign_date", type="date", nullable=true)
     */
    protected $signDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="activation_date", type="date", nullable=true)
     */
    protected $activationDate;

    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="text", nullable=true)
     */
    protected $comments;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_marked_to_generate_invoice", type="boolean", nullable=true, options={"default": 0})
     */
    protected $isMarkedToGenerateInvoice;

    /**
     * @return bool
     */
    public function getIsMarkedToGenerateInvoice()
    {
        return $this->isMarkedToGenerateInvoice;
    }

    /**
     * @param bool $isMarkedToGenerateInvoice
     */
    public function setIsMarkedToGenerateInvoice($isMarkedToGenerateInvoice)
    {
        $this->isMarkedToGenerateInvoice = $isMarkedToGenerateInvoice;
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="is_call_center", type="boolean", options={"default": 0})
     */
    protected $isCallCenter = false;

    /**
     * @return bool
     */
    public function getIsCallCenter()
    {
        return $this->isCallCenter;
    }

    /**
     * @param bool $isCallCenter
     */
    public function setIsCallCenter($isCallCenter)
    {
        $this->isCallCenter = $isCallCenter;
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="is_invoice_generated", type="boolean", nullable=true, options={"default": 0})
     */
    protected $isInvoiceGenerated;

    /**
     * @return bool
     */
    public function getIsInvoiceGenerated()
    {
        return $this->isInvoiceGenerated;
    }

    /**
     * @param bool $isInvoiceGenerated
     */
    public function setIsInvoiceGenerated($isInvoiceGenerated)
    {
        $this->isInvoiceGenerated = $isInvoiceGenerated;
    }





    /**
     * @var boolean
     *
     * @ORM\Column(name="is_termination_sent", type="boolean", nullable=true)
     */
    protected $isTerminationSent;

    /**
     * @return boolean
     */
    public function getIsTerminationSent()
    {
        return $this->isTerminationSent;
    }

    /**
     * @param boolean $isTerminationSent
     */
    public function setIsTerminationSent($isTerminationSent)
    {
        $this->isTerminationSent = $isTerminationSent;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="termination_created_date", type="date", nullable=true)
     */
    protected $terminationCreatedDate;

    /**
     * @return \DateTime
     */
    public function getTerminationCreatedDate()
    {
        return $this->terminationCreatedDate;
    }

    /**
     * @param \DateTime $terminationCreatedDate
     */
    public function setTerminationCreatedDate($terminationCreatedDate)
    {
        $this->terminationCreatedDate = $terminationCreatedDate;
    }

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_proposal_osd_sent", type="boolean", nullable=true)
     */
    protected $isProposalOsdSent;

    /**
     * @return bool
     */
    public function getIsProposalOsdSent()
    {
        return $this->isProposalOsdSent;
    }

    /**
     * @param bool $isProposalOsdSent
     */
    public function setIsProposalOsdSent($isProposalOsdSent)
    {
        $this->isProposalOsdSent = $isProposalOsdSent;
    }


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="planned_activation_date", type="date", nullable=true)
     */
    protected $plannedActivationDate;

    /**
     * @return \DateTime
     */
    public function getPlannedActivationDate()
    {
        return $this->plannedActivationDate;
    }

    /**
     * @param \DateTime $plannedActivationDate
     */
    public function setPlannedActivationDate($plannedActivationDate)
    {
        $this->plannedActivationDate = $plannedActivationDate;
    }

    /**
     * @var boolean
     *
     * @ORM\Column(name="proposal_status", type="boolean", nullable=true)
     */
    protected $proposalStatus;

    /**
     * @return bool
     */
    public function getProposalStatus()
    {
        return $this->proposalStatus;
    }

    /**
     * @param bool $proposalStatus
     */
    public function setProposalStatus($proposalStatus)
    {
        $this->proposalStatus = $proposalStatus;
    }

    /**
     * @var string
     * 
     * @ORM\Column(name="register_number", type="string", length=255, nullable=true)
     */
    protected $registerNumber;

    /**
     * @return string
     */
    public function getRegisterNumber()
    {
        return $this->registerNumber;
    }

    /**
     * @param string $number
     * @return ContractEnergyBase
     */
    public function setRegisterNumber($number)
    {
        $this->registerNumber = $number;
        return $this;
    }

    /**
     * @var string
     * 
     * @ORM\Column(name="box", type="string", length=255, nullable=true)
     */
    protected $box;

    /**
     * @return string
     */
    public function getBox()
    {
        return $this->box;
    }

    /**
     * @param string $box
     * @return ContractEnergyBase
     */
    public function setBox($box)
    {
        $this->box = $box;
        return $this;
    }


    // CHANGES
    ///////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////

    /**
     * It will return true for contracts that were invoiced at least once (proforma documents)
     */
    public function invoicedAtLeastOnce()
    {
        return $this->beforeInvoicingPeriod ? true : false;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="before_invoicing_period", type="datetime", nullable=true)
     */
    protected $beforeInvoicingPeriod;

    /**
     * @return \DateTime
     */
    public function getBeforeInvoicingPeriod()
    {
        return $this->beforeInvoicingPeriod;
    }

    /**
     * @param \DateTime $beforeInvoicingPeriod
     */
    public function setBeforeInvoicingPeriod($beforeInvoicingPeriod)
    {
        $this->beforeInvoicingPeriod = $beforeInvoicingPeriod;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="next_invoicing_period", type="datetime", nullable=true)
     */
    protected $nextInvoicingPeriod;

    /**
     * @return \DateTime
     */
    public function getNextInvoicingPeriod()
    {
        return $this->nextInvoicingPeriod;
    }

    /**
     * @param \DateTime $nextInvoicingPeriod
     */
    public function setNextInvoicingPeriod($nextInvoicingPeriod)
    {
        $this->nextInvoicingPeriod = $nextInvoicingPeriod;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="second_person_name", type="string", length=255, nullable=true)
     */
    protected $secondPersonName;

    /**
     * @var string
     *
     * @ORM\Column(name="second_person_surname", type="string", length=255, nullable=true)
     */
    protected $secondPersonSurname;

    /**
     * @var string
     *
     * @ORM\Column(name="second_person_pesel", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 11,
     *     max = 11
     * )
     */
    protected $secondPersonPesel;

    /**
     * @return string
     */
    public function getSecondPersonPesel()
    {
        return $this->secondPersonPesel;
    }

    /**
     * @param string $secondPersonPesel
     */
    public function setSecondPersonPesel($secondPersonPesel)
    {
        $this->secondPersonPesel = $secondPersonPesel;
    }

    /**
     * @return string
     */
    public function getSecondPersonName()
    {
        return $this->secondPersonName;
    }

    /**
     * @param string $secondPersonName
     */
    public function setSecondPersonName($secondPersonName)
    {
        $this->secondPersonName = $secondPersonName;
    }

    /**
     * @return string
     */
    public function getSecondPersonSurname()
    {
        return $this->secondPersonSurname;
    }

    /**
     * @param string $secondPersonSurname
     */
    public function setSecondPersonSurname($secondPersonSurname)
    {
        $this->secondPersonSurname = $secondPersonSurname;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="advisor_code", type="string", nullable=true)
     */
    protected $advisorCode;

    /**
     * @return string
     */
    public function getAdvisorCode()
    {
        return $this->advisorCode;
    }

    /**
     * @param string $advisorCode
     */
    public function setAdvisorCode($advisorCode)
    {
        $this->advisorCode = $advisorCode;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="period_in_month", type="integer", nullable=true)
     */
    protected $periodInMonths;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Seller")
     * @ORM\JoinColumn(name="seller_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $currentSellerObject;

    /**
     * @return mixed
     */
    public function getCurrentSellerObject()
    {
        return $this->currentSellerObject;
    }

    /**
     * @param mixed $currentSellerObject
     */
    public function setCurrentSellerObject($currentSellerObject)
    {
        $this->currentSellerObject = $currentSellerObject;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="current_seller", type="string", nullable=true)
     */
    protected $currentSeller;

    /**
     * @var string
     *
     * @ORM\Column(name="change_of_seller", type="string", nullable=true)
     */
    protected $changeOfSeller;

    /**
     * @return string
     */
    public function getChangeOfSeller()
    {
        return $this->changeOfSeller;
    }

    /**
     * @param string $changeOfSeller
     */
    public function setChangeOfSeller($changeOfSeller)
    {
        $this->changeOfSeller = $changeOfSeller;

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="pp_name", type="string", nullable=true)
     */
    protected $ppName;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_zipcode", type="string", nullable=true)
     */
    protected $ppZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_post_office", type="string", nullable=true)
     */
    protected $ppPostOffice;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_city", type="string", nullable=true)
     */
    protected $ppCity;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_street", type="string", nullable=true)
     */
    protected $ppStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_house_nr", type="string", nullable=true)
     */
    protected $ppHouseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="pp_apartment_nr", type="string", nullable=true)
     */
    protected $ppApartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="ppe_code", type="string", nullable=true)
     */
    protected $ppCode;

    /**
     * @var string
     *
     * @ORM\Column(name="agent", type="string", nullable=true)
     */
    protected $agent;

    /**
     * @return string
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * @param string $agent
     */
    public function setAgent($agent)
    {
        $this->agent = $agent;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="tariff_group", type="string", nullable=true)
     */
    protected $tariffGroup;
    
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $priceList;

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

    protected $contractAndPriceLists;

    public function getPriceListByDate($date)
    {
        return $this->getAssociatedObjectByDate($this->contractAndPriceLists, $date, 'getPriceList', 'getFromDate');
    }

    protected function getAssociatedObjectByDate(&$collection, $date, $associatedObjectGetMethodName, $getDateMethodName)
    {
        if (!count($collection)) {
            return null;
        }

        /** @var ContractAndPriceListInterface $before */
        $before = null;
        $associatedObjectWithoutDate = null;
        /** @var ContractAndPriceListInterface $item */
        foreach ($collection as $item) {
            // if date is not specified -> select first price list
            if (!$date) {
                return $item->$associatedObjectGetMethodName();
            }

            /** @var \DateTime $fromDate */
            $fromDate = $item->$getDateMethodName();

            // if date is not set save record for later use (when no records were chosen - choose it)
            if (!$fromDate) {
                // saves only first record without date
                if (!$associatedObjectWithoutDate) {
                    $associatedObjectWithoutDate = $item->$associatedObjectGetMethodName();
                }
                continue;
            }
            $fromDate->setTime(0, 0, 0);

            // record have date, so get before from date eq or gt provided date
            if ($before && $fromDate >= $before->$getDateMethodName() && $fromDate <= $date) {
                $before = $item;
            }

            // initial set
            if ($before === null && $fromDate <= $date) {
                $before = $item;
            }
        }

        return $before ? $before->$associatedObjectGetMethodName() : ($associatedObjectWithoutDate ?: null);
    }

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\Tariff")
     * @ORM\JoinColumn(name="tariff_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $tariff;


    protected $contractAndTariffDistributions;

    /**
     * @return mixed
     */
    public function getTariff()
    {
        return $this->tariff;
    }

    /**
     * @param mixed $tariff
     */
    public function setTariff($tariff)
    {
        $this->tariff = $tariff;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="consumption", type="string", nullable=true)
     */
    protected $consumption;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="sales_representative_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $salesRepresentative;

    /**
     * @return mixed
     */
    public function getSalesRepresentative()
    {
        return $this->salesRepresentative;
    }

    /**
     * @param mixed $salesRepresentative
     */
    public function setSalesRepresentative($salesRepresentative)
    {
        $this->salesRepresentative = $salesRepresentative;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentSeller()
    {
        return $this->currentSeller;
    }

    /**
     * @param string $currentSeller
     */
    public function setCurrentSeller($currentSeller)
    {
        $this->currentSeller = $currentSeller;

        return $this;
    }

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
    public function getPpPostOffice()
    {
        return $this->ppPostOffice;
    }

    /**
     * @param string $ppPostOffice
     */
    public function setPpPostOffice($ppPostOffice)
    {
        $this->ppPostOffice = $ppPostOffice;
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
    public function getPpCode()
    {
        return $this->ppCode;
    }

    /**
     * @param string $ppCode
     */
    public function setPpCode($ppCode)
    {
        $this->ppCode = $ppCode;
    }

    /**
     * @return mixed
     */
    public function getTariffGroup()
    {
        return $this->tariffGroup;
    }

    /**
     * @param mixed $tariffGroup
     */
    public function setTariffGroup($tariffGroup)
    {
        $this->tariffGroup = $tariffGroup;

        return $this;
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $user;

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }




    /**
     * @var bool
     *
     * @ORM\Column(name="is_marked_to_debit_note", type="boolean")
     */
    protected $isMarkedToDebitNote = false;

    /**
     * @return bool
     */
    public function getIsMarkedToDebitNote()
    {
        return $this->isMarkedToDebitNote;
    }

    /**
     * @param bool $isMarkedToDebitNote
     */
    public function setIsMarkedToDebitNote($isMarkedToDebitNote)
    {
        $this->isMarkedToDebitNote = $isMarkedToDebitNote;
    }



    /**
     * @var bool
     *
     * @ORM\Column(name="is_on_package_list", type="boolean", options={"default": 0})
     */
    protected $isOnPackageList;

    /**
     * @return bool
     */
    public function getIsOnPackageList()
    {
        return $this->isOnPackageList;
    }

    /**
     * @param bool $isOnPackageList
     */
    public function setIsOnPackageList($isOnPackageList)
    {
        $this->isOnPackageList = $isOnPackageList;

        return $this;
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="is_returned", type="boolean", options={"default": 0})
     */
    protected $isReturned;

    /**
     * @return mixed
     */
    public function getIsReturned()
    {
        return $this->isReturned;
    }

    /**
     * @param mixed $isReturned
     */
    public function setIsReturned($isReturned)
    {
        $this->isReturned = $isReturned;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\PackageToSend")
     * @ORM\JoinColumn(name="package_to_send_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $packageToSend;

    /**
     * @return mixed
     */
    public function getPackageToSend()
    {
        return $this->packageToSend;
    }

    /**
     * @param mixed $packageToSend
     */
    public function setPackageToSend($packageToSend)
    {
        $this->packageToSend = $packageToSend;

        return $this;
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="is_downloaded", type="boolean", options={"default": 0})
     */
    protected $isDownloaded;

    /**
     * @return bool
     */
    public function getIsDownloaded()
    {
        return $this->isDownloaded;
    }

    /**
     * @param bool $isDownloaded
     */
    public function setIsDownloaded($isDownloaded)
    {
        $this->isDownloaded = $isDownloaded;

        return $this;
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Distributor")
     * @ORM\JoinColumn(name="distributor_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $distributorObject;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\DistributorBranch")
     * @ORM\JoinColumn(name="distributor_branch_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $distributorBranchObject;

    /**
     * @return mixed
     */
    public function getDistributorObject()
    {
        return $this->distributorObject;
    }

    /**
     * @param mixed $distributorObject
     */
    public function setDistributorObject($distributorObject)
    {
        $this->distributorObject = $distributorObject;
    }

    /**
     * @return mixed
     */
    public function getDistributorBranchObject()
    {
        return $this->distributorBranchObject;
    }

    /**
     * @param mixed $distributorBranchObject
     */
    public function setDistributorBranchObject($distributorBranchObject)
    {
        $this->distributorBranchObject = $distributorBranchObject;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="distributor", type="string", nullable=true)
     */
    protected $distributor;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_branch", type="string", nullable=true)
     */
    protected $distributorBranch;

    /**
     * @return string
     */
    public function getDistributor()
    {
        return $this->distributor;
    }

    /**
     * @param string $distributor
     */
    public function setDistributor($distributor)
    {
        $this->distributor = $distributor;

        return $this;
    }

    /**
     * @return string
     */
    public function getDistributorBranch()
    {
        return $this->distributorBranch;
    }

    /**
     * @param string $distributorBranch
     */
    public function setDistributorBranch($distributorBranch)
    {
        $this->distributorBranch = $distributorBranch;

        return $this;
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
     * Set contractNumber
     *
     * @param string $contractNumber
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;

        return $this;
    }

    /**
     * Get contractNumber
     *
     * @return string
     */
    public function getContractNumber()
    {
        return $this->contractNumber;
    }

    /**
     * Set signDate
     *
     * @param \DateTime $signDate
     */
    public function setSignDate($signDate)
    {
        $this->signDate = $signDate;

        return $this;
    }

    /**
     * Get signDate
     *
     * @return \DateTime
     */
    public function getSignDate()
    {
        return $this->signDate;
    }

    /**
     * @return \DateTime
     */
    public function getActivationDate()
    {
        return $this->activationDate;
    }

    /**
     * @param \DateTime $activationDate
     */
    public function setActivationDate($activationDate)
    {
        $this->activationDate = $activationDate;
    }

    /**
     * Set comments
     *
     * @param string $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
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

        // Clear postponed deadlines if  isPostponed is set to false(in case someone reveals the collection form in html)
        if(!$this->isPostponed && count($this->postponedDeadlines)) {
            $this->postponedDeadlines = [];
        }
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="is_resignation", type="boolean", options={"default": 0}, nullable=true)
     */
    protected $isResignation;

    public function getIsResignation()
    {
        return $this->isResignation;
    }

    public function setIsResignation($isResignation)
    {
        $this->isResignation = $isResignation;

        return $this;
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="is_broken_contract", type="boolean", options={"default": 0}, nullable=true)
     */
    protected $isBrokenContract;

    /**
     * @return bool
     */
    public function getIsBrokenContract()
    {
        return $this->isBrokenContract;
    }

    /**
     * @param bool $isBrokenContract
     */
    public function setIsBrokenContract($isBrokenContract)
    {
        $this->isBrokenContract = $isBrokenContract;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPeriodInMonths()
    {
        return $this->periodInMonths;
    }

    /**
     * @param mixed $periodInMonths
     */
    public function setPeriodInMonths($periodInMonths)
    {
        $this->periodInMonths = $periodInMonths;
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="is_rebate_marketing_agreement", type="boolean", nullable=true)
     */
    protected $isRebateMarketingAgreement;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_rebate_timely_payments", type="boolean", nullable=true)
     */
    protected $isRebateTimelyPayments;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_rebate_electronic_invoice", type="boolean", nullable=true)
     */
    protected $isRebateElectronicInvoice;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_marketing_agreement_colonnade", type="boolean", nullable=true)
     */
    protected $isMarketingAgreementColonnade;

    /**
     * @return bool
     */
    public function getIsMarketingAgreementColonnade()
    {
        return $this->isMarketingAgreementColonnade;
    }

    /**
     * @param bool $isMarketingAgreementColonnade
     */
    public function setIsMarketingAgreementColonnade($isMarketingAgreementColonnade)
    {
        $this->isMarketingAgreementColonnade = $isMarketingAgreementColonnade;
    }

    /**
     * @return bool
     */
    public function getIsRebateMarketingAgreement()
    {
        return $this->isRebateMarketingAgreement;
    }

    /**
     * @param bool $isRebateMarketingAgreement
     */
    public function setIsRebateMarketingAgreement($isRebateMarketingAgreement)
    {
        $this->isRebateMarketingAgreement = $isRebateMarketingAgreement;
    }

    /**
     * @return bool
     */
    public function getIsRebateTimelyPayments()
    {
        return $this->isRebateTimelyPayments;
    }

    /**
     * @param bool $isRebateTimelyPayments
     */
    public function setIsRebateTimelyPayments($isRebateTimelyPayments)
    {
        $this->isRebateTimelyPayments = $isRebateTimelyPayments;
    }

    /**
     * @return bool
     */
    public function getIsRebateElectronicInvoice()
    {
        return $this->isRebateElectronicInvoice;
    }

    /**
     * @param bool $isRebateElectronicInvoice
     */
    public function setIsRebateElectronicInvoice($isRebateElectronicInvoice)
    {
        $this->isRebateElectronicInvoice = $isRebateElectronicInvoice;
    }

    /**
     * @var bool
     * 
     * @ORM\Column(name="is_canceled", type="boolean", nullable=true)
     */
    protected $isCanceled = 0;

    /**
     * Get isCanceled
     * 
     * @return bool
     */
    public function getIsCanceled()
    {
        return $this->isCanceled;
    }

    /**
     * Set isCanceled
     * 
     * @param bool $state
     * @return ContractEnergyBase
     */
    public function setIsCanceled($state)
    {
        $this->isCanceled = $state;
        return $this;
    }

    /**
     * @var bool
     * 
     * @ORM\Column(name="is_postponed", type="boolean", nullable=true)
     */
    protected $isPostponed;

    /**
     * Get isPostponed
     * 
     * @return bool
     */
    public function getIsPostponed()
    {
        return $this->isPostponed;
    }

    /**
     * Set isPostponed
     * 
     * @param bool $state
     * @return ContractEnergyBase
     */
    public function setIsPostponed($state)
    {
        $this->isPostponed = $state;
        return $this;
    }

    /**
     * @var array
     * 
     * @ORM\Column(name="postponed_deadlines", type="array", nullable=true)
     */
    protected $postponedDeadlines;

    /**
     * Get postponedDeadlines
     * 
     * @return array
     */
    public function getPostponedDeadlines()
    {
        return $this->postponedDeadlines;
    }

    /**
     * Set postponedDeadlines
     * 
     * @param array $deadlines
     * @return ContractEnergyBase
     */
    public function setPostponedDeadlines($array)
    {
        $this->postponedDeadlines = $array;
        return $this;
    }

    public function __toString()
    {
        if (!$this->getContractNumber()) {
            return 'brak numeru umowy';
        }
        return $this->getContractNumber();
    }

    // POSTPONED DEADLINES UTILITY
    public function getNewestPostponedDeadline()
    {
        $result = $this->postponedDeadlines;
        if(!$result || count($result) === 0) {
            return null;
        } else {
            return $result[count($result) - 1];
        }
    }

    public function getNewestPostponedDeadlineIsTerminationSent()
    {
        $result = $this->getNewestPostponedDeadline();
        if(!$result) {
            return null;
        } else {
            return $result['isTerminationSent'];
        }
    }

    public function getNewestPostponedDeadlineTerminationCreatedDate()
    {
        $result = $this->getNewestPostponedDeadline();
        if(!$result) {
            return null;
        } else {
            return $result['terminationCreatedDate'];
        }
    }

    public function getNewestPostponedDeadlineIsProposalOsdSent()
    {
        $result = $this->getNewestPostponedDeadline();
        if(!$result) {
            return null;
        } else {
            return $result['isProposalOsdSent'];
        }
    }

    public function getNewestPostponedDeadlinePlannedActivationDate()
    {
        $result = $this->getNewestPostponedDeadline();
        if(!$result) {
            return null;
        } else {
            return $result['plannedActivationDate'];
        }
        
    }

    public function getNewestPostponedDeadlineProposalStatus()
    {
        $result = $this->getNewestPostponedDeadline();
        if(!$result) {
            return null;
        } else {
            return $result['proposalStatus'];
        }
    }
}

