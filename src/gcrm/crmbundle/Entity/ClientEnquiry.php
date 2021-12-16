<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use GCRM\CRMBundle\Entity\ClientEnquiryAttachment;

/**
 * @ORM\Table(name="client_enquiry")
 * @ORM\Entity()
 * @Vich\Uploadable
 * @ORM\HasLifecycleCallbacks()
 */
class ClientEnquiry
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="surname", type="string", length=255, nullable=true)
     */
    private $surname;

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
     * @ORM\Column(name="street", type="string", length=255, nullable=true)
     */
    private $street;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255, nullable=true)
     */
    private $city;

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

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_street", type="string", length=255, nullable=true)
     */
    private $deliveryStreet;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_city", type="string", length=255, nullable=true)
     */
    private $deliveryCity;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_house_nr", type="string", length=255, nullable=true)
     */
    private $deliveryHouseNr;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_apartment_nr", type="string", length=255, nullable=true)
     */
    private $deliveryApartmentNr;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_zip_code", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     min = 6,
     *     max = 6
     * )
     * @Assert\Regex("/^[0-9]{2}-[0-9]{3}$/")
     */
    private $deliveryZipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_post_office", type="string", length=255, nullable=true)
     */
    private $deliveryPostOffice;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_county", type="string", length=255, nullable=true)
     */
    private $deliveryCounty;

    /**
     * @ORM\ManyToOne (targetEntity="Wecoders\EnergyBundle\Entity\Tariff")
     * @ORM\JoinColumn(name="tariff_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $tariff;

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
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\ClientEnquiryAttachment", mappedBy="clientEnquiry", cascade={"persist","remove"}, orphanRemoval=true)
     */
    protected $enquiryAttachments;

    public function addEnquiryAttachment(ClientEnquiryAttachment $enquiryAttachment)
    {
        $this->enquiryAttachments[] = $enquiryAttachment;
        $enquiryAttachment->setClientEnquiry($this);

        return $this;
    }

    public function removeEnquiryAttachment($enquiryAttachment)
    {
        $this->enquiryAttachments->removeElement($enquiryAttachment);
    }

    public function getEnquiryAttachments()
    {
        return $this->enquiryAttachments;
    }

    public function setEnquiryAttachments($enquiryAttachment)
    {
        $this->enquiryAttachments = $enquiryAttachment;
    }

    public function __construct()
    {
        $this->enquiryAttachments = new ArrayCollection();
    }

    public function getFullName()
    {
        return $this->getName() . ' ' . $this->getSurname();
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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * @param string $surname
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getTelephoneNr()
    {
        return $this->telephoneNr;
    }

    /**
     * @param string $telephoneNr
     */
    public function setTelephoneNr($telephoneNr)
    {
        $this->telephoneNr = $telephoneNr;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getHouseNr()
    {
        return $this->houseNr;
    }

    /**
     * @param string $houseNr
     */
    public function setHouseNr($houseNr)
    {
        $this->houseNr = $houseNr;
    }

    /**
     * @return string
     */
    public function getApartmentNr()
    {
        return $this->apartmentNr;
    }

    /**
     * @param string $apartmentNr
     */
    public function setApartmentNr($apartmentNr)
    {
        $this->apartmentNr = $apartmentNr;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return string
     */
    public function getPostOffice()
    {
        return $this->postOffice;
    }

    /**
     * @param string $postOffice
     */
    public function setPostOffice($postOffice)
    {
        $this->postOffice = $postOffice;
    }

    /**
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * @param string $county
     */
    public function setCounty($county)
    {
        $this->county = $county;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param string $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
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
    }



    /**
     * @var integer
     *
     * @ORM\Column(name="client_type", type="integer", nullable=false)
     */
    private $clientType;

    /**
     * @var integer
     *
     * @ORM\Column(name="energy_type", type="integer", nullable=false)
     */
    private $energyType;

    /**
     * @return int
     */
    public function getClientType()
    {
        return $this->clientType;
    }

    /**
     * @param int $clientType
     */
    public function setClientType($clientType)
    {
        $this->clientType = $clientType;
    }

    /**
     * @return int
     */
    public function getEnergyType()
    {
        return $this->energyType;
    }

    /**
     * @param int $energyType
     */
    public function setEnergyType($energyType)
    {
        $this->energyType = $energyType;
    }

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
     * @var bool
     *
     * @ORM\Column(name="is_rebate_marketing_agreement", type="boolean", nullable=true)
     */
    protected $isRebateMarketingAgreement;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor", type="string", nullable=true)
     */
    protected $distributor;

    /**
     * @return string
     */
    public function getPesel()
    {
        return $this->pesel;
    }

    /**
     * @param string $pesel
     */
    public function setPesel($pesel)
    {
        $this->pesel = $pesel;
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
    }

    /**
     * @return bool
     */
    public function isRebateMarketingAgreement()
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
    }

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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Distributor")
     * @ORM\JoinColumn(name="distributor_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $distributorObject;

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
     * @return string
     */
    public function getDeliveryStreet()
    {
        return $this->deliveryStreet;
    }

    /**
     * @param string $deliveryStreet
     */
    public function setDeliveryStreet($deliveryStreet)
    {
        $this->deliveryStreet = $deliveryStreet;
    }

    /**
     * @return string
     */
    public function getDeliveryCity()
    {
        return $this->deliveryCity;
    }

    /**
     * @param string $deliveryCity
     */
    public function setDeliveryCity($deliveryCity)
    {
        $this->deliveryCity = $deliveryCity;
    }

    /**
     * @return string
     */
    public function getDeliveryHouseNr()
    {
        return $this->deliveryHouseNr;
    }

    /**
     * @param string $deliveryHouseNr
     */
    public function setDeliveryHouseNr($deliveryHouseNr)
    {
        $this->deliveryHouseNr = $deliveryHouseNr;
    }

    /**
     * @return string
     */
    public function getDeliveryApartmentNr()
    {
        return $this->deliveryApartmentNr;
    }

    /**
     * @param string $deliveryApartmentNr
     */
    public function setDeliveryApartmentNr($deliveryApartmentNr)
    {
        $this->deliveryApartmentNr = $deliveryApartmentNr;
    }

    /**
     * @return string
     */
    public function getDeliveryZipCode()
    {
        return $this->deliveryZipCode;
    }

    /**
     * @param string $deliveryZipCode
     */
    public function setDeliveryZipCode($deliveryZipCode)
    {
        $this->deliveryZipCode = $deliveryZipCode;
    }

    /**
     * @return string
     */
    public function getDeliveryPostOffice()
    {
        return $this->deliveryPostOffice;
    }

    /**
     * @param string $deliveryPostOffice
     */
    public function setDeliveryPostOffice($deliveryPostOffice)
    {
        $this->deliveryPostOffice = $deliveryPostOffice;
    }

    /**
     * @return string
     */
    public function getDeliveryCounty()
    {
        return $this->deliveryCounty;
    }

    /**
     * @param string $deliveryCounty
     */
    public function setDeliveryCounty($deliveryCounty)
    {
        $this->deliveryCounty = $deliveryCounty;
    }


}