<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use GCRM\CRMBundle\Service\AccountNumberInterface;
use Symfony\Component\Validator\Constraints as Assert;
use TZiebura\SmsBundle\Interfaces\SmsClientInterface;

/**
 * Client
 *
 * @ORM\Table(name="client")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ClientRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Client implements SmsClientInterface, AccountNumberInterface
{
    // buyer







    // recipient

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_company_name", type="string", length=255, nullable=true)
     */
    private $recipientCompanyName;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_nip", type="string", length=255, nullable=true)
     */
    private $recipientNip;

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
     * @var string
     *
     * @ORM\Column(name="recipient_city", type="string", length=255, nullable=true)
     */
    private $recipientCity;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_street", type="string", length=255, nullable=true)
     */
    private $recipientStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_house_nr", type="string", length=255, nullable=true)
     */
    private $recipientHouseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_apartment_nr", type="string", length=255, nullable=true)
     */
    private $recipientApartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="recipient_zip_code", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 6,
     *     max = 6
     * )
     * @Assert\Regex("/^[0-9]{2}-[0-9]{3}$/")
     */
    private $recipientZipCode;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_recipient_same_as_buyer", type="boolean", nullable=true, options={"default": 0})
     */
    private $isRecipientSameAsBuyer;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_company", type="boolean", options={"default": 0})
     */
    private $isCompany = false;

    /**
     * @return bool
     */
    public function getIsCompany()
    {
        return $this->isCompany;
    }

    /**
     * @param bool $isCompany
     */
    public function setIsCompany($isCompany)
    {
        $this->isCompany = $isCompany;
    }





    // payer

    /**
     * @var string
     *
     * @ORM\Column(name="payer_company_name", type="string", length=255, nullable=true)
     */
    private $payerCompanyName;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_nip", type="string", length=255, nullable=true)
     */
    private $payerNip;

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
     * @var string
     *
     * @ORM\Column(name="payer_city", type="string", length=255, nullable=true)
     */
    private $payerCity;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_street", type="string", length=255, nullable=true)
     */
    private $payerStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_house_nr", type="string", length=255, nullable=true)
     */
    private $payerHouseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_apartment_nr", type="string", length=255, nullable=true)
     */
    private $payerApartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="payer_zip_code", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 6,
     *     max = 6
     * )
     * @Assert\Regex("/^[0-9]{2}-[0-9]{3}$/")
     */
    private $payerZipCode;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_payer_same_as_buyer", type="boolean", nullable=true, options={"default": 0})
     */
    private $isPayerSameAsBuyer;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_payer_same_as_recipient", type="boolean", nullable=true, options={"default": 0})
     */
    private $isPayerSameAsRecipient;


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
     * @return bool
     */
    public function getIsRecipientSameAsBuyer()
    {
        return $this->isRecipientSameAsBuyer;
    }

    /**
     * @param bool $isRecipientSameAsBuyer
     */
    public function setIsRecipientSameAsBuyer($isRecipientSameAsBuyer)
    {
        $this->isRecipientSameAsBuyer = $isRecipientSameAsBuyer;
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
     * @return bool
     */
    public function getIsPayerSameAsBuyer()
    {
        return $this->isPayerSameAsBuyer;
    }

    /**
     * @param bool $isPayerSameAsBuyer
     */
    public function setIsPayerSameAsBuyer($isPayerSameAsBuyer)
    {
        $this->isPayerSameAsBuyer = $isPayerSameAsBuyer;
    }

    /**
     * @return bool
     */
    public function getIsPayerSameAsRecipient()
    {
        return $this->isPayerSameAsRecipient;
    }

    /**
     * @param bool $isPayerSameAsRecipient
     */
    public function setIsPayerSameAsRecipient($isPayerSameAsRecipient)
    {
        $this->isPayerSameAsRecipient = $isPayerSameAsRecipient;
    }














    /**
     * @var \DateTime
     *
     * @ORM\Column(name="before_payment_request_period", type="datetime", nullable=true)
     */
    protected $beforePaymentRequestPeriod;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="next_payment_request_period", type="datetime", nullable=true)
     */
    protected $nextPaymentRequestPeriod;

    /**
     * @return \DateTime
     */
    public function getBeforePaymentRequestPeriod()
    {
        return $this->beforePaymentRequestPeriod;
    }

    /**
     * @param \DateTime $beforePaymentRequestPeriod
     */
    public function setBeforePaymentRequestPeriod($beforePaymentRequestPeriod)
    {
        $this->beforePaymentRequestPeriod = $beforePaymentRequestPeriod;
    }

    /**
     * @return \DateTime
     */
    public function getNextPaymentRequestPeriod()
    {
        return $this->nextPaymentRequestPeriod;
    }

    /**
     * @param \DateTime $nextPaymentRequestPeriod
     */
    public function setNextPaymentRequestPeriod($nextPaymentRequestPeriod)
    {
        $this->nextPaymentRequestPeriod = $nextPaymentRequestPeriod;
    }

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ClientAndContractGas", mappedBy="client", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $clientAndGasContracts;

    public function addClientAndGasContract(ClientAndContractGas $clientAndGasContract)
    {
        $this->clientAndGasContracts[] = $clientAndGasContract;
        $clientAndGasContract->setClient($this);

        return $this;
    }

    public function removeClientAndGasContract(ClientAndContractGas $clientAndGasContract)
    {
        $this->clientAndGasContracts->removeElement($clientAndGasContract);
    }

    public function getClientAndGasContracts()
    {
        return $this->clientAndGasContracts;
    }

    public function setClientAndGasContracts($clientAndGasContracts)
    {
        $this->clientAndGasContracts = $clientAndGasContracts;
    }

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ClientAndContractEnergy", mappedBy="client", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $clientAndEnergyContracts;

    public function addClientAndEnergyContract(ClientAndContractEnergy $clientAndEnergyContract)
    {
        $this->clientAndEnergyContracts[] = $clientAndEnergyContract;
        $clientAndEnergyContract->setClient($this);

        return $this;
    }

    public function removeClientAndEnergyContract(ClientAndContractEnergy $clientAndEnergyContract)
    {
        $this->clientAndEnergyContracts->removeElement($clientAndEnergyContract);
    }

    public function getClientAndEnergyContracts()
    {
        return $this->clientAndEnergyContracts;
    }

    public function setClientAndEnergyContracts($clientAndEnergyContracts)
    {
        $this->clientAndEnergyContracts = $clientAndEnergyContracts;
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Branch")
     * @ORM\JoinColumn(name="branch_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $branch;

    /**
     * @return mixed
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @param mixed $branch
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $user;

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="check_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $checkUser;

    public function getCheckUser()
    {
        return $this->checkUser;
    }

    public function setCheckUser($checkUser)
    {
        $this->checkUser = $checkUser;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="check_time", type="datetime", nullable=true)
     */
    private $checkTime;

    /**
     * @return \DateTime
     */
    public function getCheckTime()
    {
        return $this->checkTime;
    }

    /**
     * @param \DateTime $checkTime
     */
    public function setCheckTime($checkTime)
    {
        $this->checkTime = $checkTime;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusClient")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $status;

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus(StatusClient $status)
    {
        $this->status = $status;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusClientVerification")
     * @ORM\JoinColumn(name="status_verification_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $statusVerification;

    public function getStatusVerification()
    {
        return $this->statusVerification;
    }

    public function setStatusVerification($statusVerification)
    {
        $this->statusVerification = $statusVerification;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusDepartment")
     * @ORM\JoinColumn(name="status_department_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $statusDepartment;

    public function getStatusDepartment()
    {
        return $this->statusDepartment;
    }

    public function setStatusDepartment($statusDepartment)
    {
        $this->statusDepartment = $statusDepartment;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="company_name", type="string", length=255, nullable=true)
     */
    private $companyName;

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="surname", type="string", length=255, nullable=true)
     */
    private $surname;

    /**
     * @ORM\OneToOne(targetEntity="GCRM\CRMBundle\Entity\AccountNumberIdentifier")
     * @ORM\JoinColumn(name="account_number_identifier_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $accountNumberIdentifier;

    /**
     * @return mixed
     */
    public function getAccountNumberIdentifier()
    {
        return $this->accountNumberIdentifier;
    }

    /**
     * @param mixed $accountNumberIdentifier
     */
    public function setAccountNumberIdentifier(AccountNumberIdentifier $accountNumberIdentifier)
    {
        $this->accountNumberIdentifier = $accountNumberIdentifier;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="badge_id", type="string", length=12, nullable=true)
     */
    private $badgeId;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account_number", type="string", length=26, nullable=true)
     */
    private $bankAccountNumber;

    /**
     * @return string
     */
    public function getBankAccountNumber()
    {
        return $this->bankAccountNumber;
    }

    /**
     * @param string $bankAccountNumber
     */
    public function setBankAccountNumber($bankAccountNumber)
    {
        $this->bankAccountNumber = $bankAccountNumber;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="previous_bank_account_number", type="string", length=26, nullable=true)
     */
    private $previousBankAccountNumber;

    /**
     * @return string
     */
    public function getPreviousBankAccountNumber()
    {
        return $this->previousBankAccountNumber;
    }

    /**
     * @param string $previousBankAccountNumber
     */
    public function setPreviousBankAccountNumber($previousBankAccountNumber)
    {
        $this->previousBankAccountNumber = $previousBankAccountNumber;
    }

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

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="telephone_nr", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 9,
     *     max = 9
     * )
     * @Assert\Regex("/^[0-9]+$/")
     */
    private $telephoneNr;

    /**
     * @var string
     *
     * @ORM\Column(name="pesel", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 11,
     *     max = 11
     * )
     */
    private $pesel;

    public function getDateOfBirthFromPesel()
    {
        if (!$this->pesel || mb_strlen($this->pesel) != 11) {
            return null;
        }

        $date = new \DateTime();
        $month = substr($this->pesel, 2, 2);
        $yearPrefix = 19;
        if ($month > 20) {
            $yearPrefix = 20;
            $month = $month - 20;
        }

        $date->setDate($yearPrefix . substr($this->pesel, 0, 2), $month, substr($this->pesel, 4, 2));
        $date->setTime(0, 0, 0);

        return $date;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="nip", type="string", length=255, nullable=true)
     * @Assert\Regex("/^[0-9]+$/")
     * @Assert\Length(
     *     min = 10,
     *     max = 10
     * )
     */
    private $nip;

    /**
     * @var string
     *
     * @ORM\Column(name="regon", type="string", length=255, nullable=true)
     * @Assert\Regex("/^[0-9]+$/")
     * @Assert\Length(
     *     min = 9,
     *     max = 14
     * )
     */
    private $regon;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="id_nr", type="string", length=255, nullable=true)
     */
    private $idNr;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="street", type="string", length=255, nullable=true)
     */
    private $street;

    /**
     * @var string
     *
     * @ORM\Column(name="house_nr", type="string", length=255, nullable=true)
     */
    private $houseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="apartment_nr", type="string", length=255, nullable=true)
     */
    private $apartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="zip_code", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 6,
     *     max = 6
     * )
     * @Assert\Regex("/^[0-9]{2}-[0-9]{3}$/")
     */
    private $zipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="post_office", type="string", length=255, nullable=true)
     */
    private $postOffice;

    /**
     * @var string
     *
     * @ORM\Column(name="county", type="string", length=255, nullable=true)
     */
    private $county;


    public function getToRecipientCompanyName()
    {
        return $this->manageClientRecipientDataSingleValue(['companyName', 'recipientCompanyName']);
    }
    public function getToRecipientNip()
    {
        return $this->manageClientRecipientDataSingleValue(['nip', 'recipientNip']);
    }
    public function getToRecipientZipCode()
    {
        return $this->manageClientRecipientDataSingleValue(['zipCode', 'recipientZipCode']);
    }
    public function getToRecipientCity()
    {
        return $this->manageClientRecipientDataSingleValue(['city', 'recipientCity']);
    }
    public function getToRecipientStreet()
    {
        return $this->manageClientRecipientDataSingleValue(['street', 'recipientStreet']);
    }
    public function getToRecipientHouseNr()
    {
        return $this->manageClientRecipientDataSingleValue(['houseNr', 'recipientHouseNr']);
    }
    public function getToRecipientApartmentNr()
    {
        return $this->manageClientRecipientDataSingleValue(['apartmentNr', 'recipientApartmentNr']);
    }



    public function getToPayerCompanyName()
    {
        return $this->manageClientPayerDataSingleValue(['companyName', 'recipientCompanyName', 'payerCompanyName']);
    }
    public function getToPayerNip()
    {
        return $this->manageClientPayerDataSingleValue(['nip', 'recipientNip', 'payerNip']);
    }
    public function getToPayerZipCode()
    {
        return $this->manageClientPayerDataSingleValue(['zipCode', 'recipientZipCode', 'payerZipCode']);
    }
    public function getToPayerCity()
    {
        return $this->manageClientPayerDataSingleValue(['city', 'recipientCity', 'payerCity']);
    }
    public function getToPayerStreet()
    {
        return $this->manageClientPayerDataSingleValue(['street', 'recipientStreet', 'payerStreet']);
    }
    public function getToPayerHouseNr()
    {
        return $this->manageClientPayerDataSingleValue(['houseNr', 'recipientHouseNr', 'payerHouseNr']);
    }
    public function getToPayerApartmentNr()
    {
        return $this->manageClientPayerDataSingleValue(['apartmentNr', 'recipientApartmentNr', 'payerApartmentNr']);
    }

    private function manageClientRecipientDataSingleValue($data)
    {
        if (!$this->getIsCompany()) {
            return null;
        }
        if ($this->isRecipientSameAsBuyer) {
            return $this->{$data[0]};
        }
        return $this->{$data[1]};
    }

    private function manageClientPayerDataSingleValue($data)
    {
        if (!$this->getIsCompany()) {
            return null;
        }
        if ($this->isPayerSameAsBuyer || ($this->isPayerSameAsRecipient && $this->isRecipientSameAsBuyer)) {
            return $this->{$data[0]};
        } elseif ($this->isPayerSameAsRecipient) {
            return $this->{$data[1]};
        }
        return $this->{$data[2]};
    }















    public function getToCorrespondenceCity()
    {
        if ($this->getIsCompany()) {
            if ($this->isPayerSameAsBuyer) {
                return $this->city;
            } elseif ($this->getIsPayerSameAsRecipient()) {
                if ($this->isRecipientSameAsBuyer) {
                    return $this->city;
                }
                return $this->recipientCity;
            }
            return $this->payerCity;
        } elseif ($this->isCorrespondenceData()) {
            return $this->correspondenceCity;
        }
        return $this->city;
    }

    public function getToCorrespondenceZipCode()
    {
        if ($this->getIsCompany()) {
            if ($this->isPayerSameAsBuyer) {
                return $this->zipCode;
            } elseif ($this->getIsPayerSameAsRecipient()) {
                if ($this->isRecipientSameAsBuyer) {
                    return $this->zipCode;
                }
                return $this->recipientZipCode;
            }
            return $this->payerZipCode;
        } elseif ($this->isCorrespondenceData()) {
            return $this->correspondenceZipCode;
        }
        return $this->zipCode;
    }

    public function getToCorrespondenceStreet()
    {
        if ($this->getIsCompany()) {
            if ($this->isPayerSameAsBuyer) {
                return $this->street;
            } elseif ($this->getIsPayerSameAsRecipient()) {
                if ($this->isRecipientSameAsBuyer) {
                    return $this->street;
                }
                return $this->recipientStreet;
            }
            return $this->payerStreet;
        } elseif ($this->isCorrespondenceData()) {
            return $this->correspondenceStreet;
        }
        return $this->street;
    }

    public function getToCorrespondenceHouseNr()
    {
        if ($this->getIsCompany()) {
            if ($this->isPayerSameAsBuyer) {
                return $this->houseNr;
            } elseif ($this->getIsPayerSameAsRecipient()) {
                if ($this->isRecipientSameAsBuyer) {
                    return $this->houseNr;
                }
                return $this->recipientHouseNr;
            }
            return $this->payerHouseNr;
        } elseif ($this->isCorrespondenceData()) {
            return $this->correspondenceHouseNr;
        }
        return $this->houseNr;
    }

    public function getToCorrespondenceApartmentNr()
    {
        if ($this->getIsCompany()) {
            if ($this->isPayerSameAsBuyer) {
                return $this->apartmentNr;
            } elseif ($this->getIsPayerSameAsRecipient()) {
                if ($this->isRecipientSameAsBuyer) {
                    return $this->apartmentNr;
                }
                return $this->recipientApartmentNr;
            }
            return $this->payerApartmentNr;
        } elseif ($this->isCorrespondenceData()) {
            return $this->correspondenceApartmentNr;
        }
        return $this->apartmentNr;
    }

    public function getToCorrespondenceCounty()
    {
        if ($this->getIsCompany()) {
            return null;
        } elseif ($this->isCorrespondenceData()) {
            return $this->correspondenceCounty;
        }
        return $this->county;
    }

    public function getToCorrespondencePostOffice()
    {
        if ($this->getIsCompany()) {
            return null;
        } elseif ($this->isCorrespondenceData()) {
            return $this->correspondencePostOffice;
        }
        return $this->postOffice;
    }

    public function getToContactTelephone()
    {
        if ($this->contactTelephoneNr) {
            return $this->contactTelephoneNr;
        }
        return $this->telephoneNr;
    }

    private function isCorrespondenceData()
    {
        if (
            $this->correspondenceCity &&
            $this->correspondenceZipCode &&
            $this->correspondenceStreet &&
            $this->correspondenceHouseNr
        ) {
            return true;
        }
        return false;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="contact_telephone_nr", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 9,
     *     max = 9
     * )
     * @Assert\Regex("/^[0-9]+$/")
     */
    private $contactTelephoneNr;

    /**
     * @var string
     *
     * @ORM\Column(name="correspondence_city", type="string", length=255, nullable=true)
     */
    private $correspondenceCity;

    /**
     * @var string
     *
     * @ORM\Column(name="correspondence_street", type="string", length=255, nullable=true)
     */
    private $correspondenceStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="correspondence_house_nr", type="string", length=255, nullable=true)
     */
    private $correspondenceHouseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="correspondence_apartment_nr", type="string", length=255, nullable=true)
     */
    private $correspondenceApartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="correspondence_zip_code", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 6,
     *     max = 6
     * )
     * @Assert\Regex("/^[0-9]{2}-[0-9]{3}$/")
     */
    private $correspondenceZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="correspondence_post_office", type="string", length=255, nullable=true)
     */
    private $correspondencePostOffice;

    /**
     * @var string
     *
     * @ORM\Column(name="correspondence_county", type="string", length=255, nullable=true)
     */
    private $correspondenceCounty;

    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="text", nullable=true)
     */
    private $comments;

    /**
     * @var string
     *
     * @ORM\Column(name="department_comments", type="text", nullable=true)
     */
    private $departmentComments;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_marked_to_generate_invoice", type="boolean", nullable=true, options={"default": 0})
     */
    private $isMarkedToGenerateInvoice;

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
     * @ORM\Column(name="is_invoice_generated", type="boolean", nullable=true, options={"default": 0})
     */
    private $isInvoiceGenerated;

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
     * @return string
     */
    public function getDepartmentComments()
    {
        return $this->departmentComments;
    }

    /**
     * @param string $departmentComments
     */
    public function setDepartmentComments($departmentComments)
    {
        $this->departmentComments = $departmentComments;
    }


    private $addDepartmentComment;

    public function getAddDepartmentComment()
    {
        return $this->addDepartmentComment;
    }

    public function setAddDepartmentComment($addDepartmentComment)
    {
        $now = new \DateTime();
        $data = '
Data dodania: ' . $now->format('Y-m-d H:i:s') . '
Komunikat: ' . $addDepartmentComment . '
__________________________________
        ' . $this->getDepartmentComments();
        $this->setDepartmentComments($data);
    }

    /**
     * @var array
     *
     * @ORM\Column(name="temp_new_data", type="text", nullable=true)
     */
    private $tempNewData;

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
     * @ORM\Column(name="initial_balance", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $initialBalance;

    /**
     * @return string
     */
    public function getInitialBalance()
    {
        return $this->initialBalance;
    }

    /**
     * @param string $initialBalance
     */
    public function setInitialBalance($initialBalance)
    {
        $this->initialBalance = $initialBalance;

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
     * Set contractNr
     *
     * @param string $contractNr
     *
     * @return Client
     */
    public function setContractNr($contractNr)
    {
        $this->contractNr = $contractNr;

        return $this;
    }

    /**
     * Get contractNr
     *
     * @return string
     */
    public function getContractNr()
    {
        return $this->contractNr;
    }

    public function getFullName()
    {
        return $this->getName() . ' ' . $this->getSurname();
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Client
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set surname
     *
     * @param string $surname
     *
     * @return Client
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;

        return $this;
    }

    /**
     * Get surname
     *
     * @return string
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * Set telephoneNr
     *
     * @param string $telephoneNr
     *
     * @return Client
     */
    public function setTelephoneNr($telephoneNr)
    {
        $this->telephoneNr = $telephoneNr;

        return $this;
    }

    /**
     * Get telephoneNr
     *
     * @return string
     */
    public function getTelephoneNr()
    {
        return $this->telephoneNr;
    }

    /**
     * Set pesel
     *
     * @param string $pesel
     *
     * @return Client
     */
    public function setPesel($pesel)
    {
        $this->pesel = $pesel;

        return $this;
    }

    /**
     * Get pesel
     *
     * @return string
     */
    public function getPesel()
    {
        return $this->pesel;
    }

    /**
     * Set nip
     *
     * @param string $nip
     *
     * @return Client
     */
    public function setNip($nip)
    {
        $this->nip = $nip;

        return $this;
    }

    /**
     * Get nip
     *
     * @return string
     */
    public function getNip()
    {
        return $this->nip;
    }

    /**
     * Set regon
     *
     * @param string $regon
     *
     * @return Client
     */
    public function setRegon($regon)
    {
        $this->regon = $regon;

        return $this;
    }

    /**
     * Get regon
     *
     * @return string
     */
    public function getRegon()
    {
        return $this->regon;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Client
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set idNr
     *
     * @param string $idNr
     *
     * @return Client
     */
    public function setIdNr($idNr)
    {
        $this->idNr = $idNr;

        return $this;
    }

    /**
     * Get idNr
     *
     * @return string
     */
    public function getIdNr()
    {
        return $this->idNr;
    }

    public function getAddress()
    {
        if ($this->getHouseNr() && $this->getApartmentNr()) {
            return $this->getStreet() . ' ' . $this->getHouseNr() . '/' . $this->getApartmentNr();
        } else {
            return $this->getStreet() . ' ' . $this->getHouseNr();
        }
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Client
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set street
     *
     * @param string $street
     *
     * @return Client
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set houseNr
     *
     * @param string $houseNr
     *
     * @return Client
     */
    public function setHouseNr($houseNr)
    {
        $this->houseNr = $houseNr;

        return $this;
    }

    /**
     * Get houseNr
     *
     * @return string
     */
    public function getHouseNr()
    {
        return $this->houseNr;
    }

    /**
     * Set apartmentNr
     *
     * @param string $apartmentNr
     *
     * @return Client
     */
    public function setApartmentNr($apartmentNr)
    {
        $this->apartmentNr = $apartmentNr;

        return $this;
    }

    /**
     * Get apartmentNr
     *
     * @return string
     */
    public function getApartmentNr()
    {
        return $this->apartmentNr;
    }

    /**
     * Set zipCode
     *
     * @param string $zipCode
     *
     * @return Client
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode
     *
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * Set postOffice
     *
     * @param string $postOffice
     *
     * @return Client
     */
    public function setPostOffice($postOffice)
    {
        $this->postOffice = $postOffice;

        return $this;
    }

    /**
     * Get postOffice
     *
     * @return string
     */
    public function getPostOffice()
    {
        return $this->postOffice;
    }

    /**
     * Set county
     *
     * @param string $county
     *
     * @return Client
     */
    public function setCounty($county)
    {
        $this->county = $county;

        return $this;
    }

    /**
     * Get county
     *
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Set contactTelephoneNr
     *
     * @param string $contactTelephoneNr
     *
     * @return Client
     */
    public function setContactTelephoneNr($contactTelephoneNr)
    {
        $this->contactTelephoneNr = $contactTelephoneNr;

        return $this;
    }

    /**
     * Get contactTelephoneNr
     *
     * @return string
     */
    public function getContactTelephoneNr()
    {
        return $this->contactTelephoneNr;
    }

    /**
     * Set correspondenceCity
     *
     * @param string $correspondenceCity
     *
     * @return Client
     */
    public function setCorrespondenceCity($correspondenceCity)
    {
        $this->correspondenceCity = $correspondenceCity;

        return $this;
    }

    /**
     * Get correspondenceCity
     *
     * @return string
     */
    public function getCorrespondenceCity()
    {
        return $this->correspondenceCity;
    }

    /**
     * Set correspondenceStreet
     *
     * @param string $correspondenceStreet
     *
     * @return Client
     */
    public function setCorrespondenceStreet($correspondenceStreet)
    {
        $this->correspondenceStreet = $correspondenceStreet;

        return $this;
    }

    /**
     * Get correspondenceStreet
     *
     * @return string
     */
    public function getCorrespondenceStreet()
    {
        return $this->correspondenceStreet;
    }

    /**
     * Set correspondenceHouseNr
     *
     * @param string $correspondenceHouseNr
     *
     * @return Client
     */
    public function setCorrespondenceHouseNr($correspondenceHouseNr)
    {
        $this->correspondenceHouseNr = $correspondenceHouseNr;

        return $this;
    }

    /**
     * Get correspondenceHouseNr
     *
     * @return string
     */
    public function getCorrespondenceHouseNr()
    {
        return $this->correspondenceHouseNr;
    }

    /**
     * Set correspondenceApartmentNr
     *
     * @param string $correspondenceApartmentNr
     *
     * @return Client
     */
    public function setCorrespondenceApartmentNr($correspondenceApartmentNr)
    {
        $this->correspondenceApartmentNr = $correspondenceApartmentNr;

        return $this;
    }

    /**
     * Get correspondenceApartmentNr
     *
     * @return string
     */
    public function getCorrespondenceApartmentNr()
    {
        return $this->correspondenceApartmentNr;
    }

    /**
     * Set correspondenceZipCode
     *
     * @param string $correspondenceZipCode
     *
     * @return Client
     */
    public function setCorrespondenceZipCode($correspondenceZipCode)
    {
        $this->correspondenceZipCode = $correspondenceZipCode;

        return $this;
    }

    /**
     * Get correspondenceZipCode
     *
     * @return string
     */
    public function getCorrespondenceZipCode()
    {
        return $this->correspondenceZipCode;
    }

    /**
     * Set correspondencePostOffice
     *
     * @param string $correspondencePostOffice
     *
     * @return Client
     */
    public function setCorrespondencePostOffice($correspondencePostOffice)
    {
        $this->correspondencePostOffice = $correspondencePostOffice;

        return $this;
    }

    /**
     * Get correspondencePostOffice
     *
     * @return string
     */
    public function getCorrespondencePostOffice()
    {
        return $this->correspondencePostOffice;
    }

    /**
     * Set correspondenceCounty
     *
     * @param string $correspondenceCounty
     *
     * @return Client
     */
    public function setCorrespondenceCounty($correspondenceCounty)
    {
        $this->correspondenceCounty = $correspondenceCounty;

        return $this;
    }

    /**
     * Get correspondenceCounty
     *
     * @return string
     */
    public function getCorrespondenceCounty()
    {
        return $this->correspondenceCounty;
    }

    /**
     * Set comments
     *
     * @param string $comments
     *
     * @return Client
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
     * Get tempNewData
     *
     * @return string
     */
    public function getTempNewData()
    {
        return $this->tempNewData;
    }

    /**
     * @return Client
     */
    public function setTempNewData($client)
    {
        if (is_object($client)) {
            $data = '
Użytkownik wprowadzający: ' . $client->getUser() . '
Imię: ' . $client->getName() . '
Nazwisko: ' . $client->getSurname() . '
Numer telefonu: ' . $client->gettelephoneNr() . '
PESEL: ' . $client->getPesel() . '
NIP: ' . $client->getNip() . '
REGON: ' . $client->getRegon() . '
E-mail: ' . $client->getEmail() . '
Numer dowodu: ' . $client->getIdNr() . '
Miasto: ' . $client->getCity() . '
Kod pocztowy: ' . $client->getZipCode() . '
Ulica: ' . $client->getStreet() . '
Numer domu: ' . $client->getHouseNr() . '
Numer mieszkania: ' . $client->getApartmentNr() . '
Poczta: ' . $client->getPostOffice() . '
Powiat: ' . $client->getCounty() . '
Kontakt - numer telefonu: ' . $client->getContactTelephoneNr() . '
Korespondencja - miasto: ' . $client->getCorrespondenceCity() . '
Korespondencja - kod pocztowy: ' . $client->getCorrespondenceZipCode() . '
Korespondencja - ulica: ' . $client->getCorrespondenceStreet() . '
Korespondencja - numer domu: ' . $client->getCorrespondenceHouseNr() . '
Korespondencja - numer mieszkania: ' . $client->getCorrespondenceApartmentNr() . '
Korespondencja - poczta: ' . $client->getCorrespondencePostOffice() . '
Korespondencja - powiat: ' . $client->getCorrespondenceCounty() . '
Uwagi: ' . $client->getComments() . '
';

            $this->tempNewData = $data;

        } else {
            $this->tempNewData = $client;
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return Client
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
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
     * @return Client
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
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
        $str = 
            $this->getName() . ' ' . $this->getSurname() .
            ' | Tel: ' . $this->gettelephoneNr() .
            ' | Pesel: ' . $this->getPesel()
        ;

        if($this->clientAndEnergyContracts && count($this->clientAndEnergyContracts) > 0 && $this->clientAndEnergyContracts[0]->getContract()) {
            $str .= ' | Status: ' . $this->clientAndEnergyContracts[0]->getContract()->getActualStatus();
            $str .= ' | P';
            $str .= ' | Nr. umowy: ' . $this->clientAndEnergyContracts[0]->getContract()->getContractNumber();
        }
        if($this->clientAndGasContracts && count($this->clientAndGasContracts) > 0 && $this->clientAndGasContracts[0]->getContract()) {
            $str .= ' | Status: ' . $this->clientAndGasContracts[0]->getContract()->getActualStatus();
            $str .= ' | G';
            $str .= ' | Nr. umowy: ' . $this->clientAndGasContracts[0]->getContract()->getContractNumber();
        }
        return $str;
    }

    /**
     * SmsClientInterface
     */
    public function getFirstName()
    {
        return $this->name;
    }

    public function getLastName()
    {
        return $this->surname;
    }

    public function getPhoneNumber()
    {
        return $this->telephoneNr;
    }

    public function getIdentifier()
    {
        return $this->id;
    }
    
    function __construct()
    {
        $this->clientGroups = new ArrayCollection();
    }

    /**
     * @ORM\OneToMany(targetEntity="TZiebura\CorrespondenceBundle\Entity\ThreadAndClient", mappedBy="client", cascade={"persist", "remove"})
     */
    private $threadAndClients;

    public function getThreadAndClients()
    {
        return $this->threadAndClients;
    }

    public function setThreadAndClients($threadAndClients)
    {
        $this->threadAndClients = $threadAndClients;
        return $this;
    }
}

