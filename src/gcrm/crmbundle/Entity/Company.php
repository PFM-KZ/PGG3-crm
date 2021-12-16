<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Company
 *
 * @ORM\Table(name="company")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\CompanyRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Company
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="zipcode", type="string", length=255)
     */
    private $zipcode;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=255)
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="nip", type="string", length=20)
     */
    private $nip;

    /**
     * @var string
     *
     * @ORM\Column(name="regon", type="string", length=9)
     */
    private $regon;

    /**
     * @return string
     */
    public function getRegon()
    {
        return $this->regon;
    }

    /**
     * @param string $regon
     */
    public function setRegon($regon)
    {
        $this->regon = $regon;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", length=255)
     */
    private $bankName;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_static_part_code_one", type="string", length=8, nullable=true)
     */
    private $bankGeneratorStaticPartCodeOne;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_static_part_code_two", type="string", length=4, nullable=true)
     */
    private $bankGeneratorStaticPartCodeTwo;

    /**
     * @return string
     */
    public function getBankGeneratorStaticPartCodeOne()
    {
        return $this->bankGeneratorStaticPartCodeOne;
    }

    /**
     * @param string $bankGeneratorStaticPartCodeOne
     */
    public function setBankGeneratorStaticPartCodeOne($bankGeneratorStaticPartCodeOne)
    {
        $this->bankGeneratorStaticPartCodeOne = $bankGeneratorStaticPartCodeOne;

        return $this;
    }

    /**
     * @return string
     */
    public function getBankGeneratorStaticPartCodeTwo()
    {
        return $this->bankGeneratorStaticPartCodeTwo;
    }

    /**
     * @param string $bankGeneratorStaticPartCodeTwo
     */
    public function setBankGeneratorStaticPartCodeTwo($bankGeneratorStaticPartCodeTwo)
    {
        $this->bankGeneratorStaticPartCodeTwo = $bankGeneratorStaticPartCodeTwo;

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account_number", type="string", length=255)
     */
    private $bankAccountNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="enable_bank_account_generator", type="boolean")
     */
    private $enableBankAccountGenerator;

    /**
     * @var string
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @return string
     */
    public function getEnableBankAccountGenerator()
    {
        return $this->enableBankAccountGenerator;
    }

    /**
     * @param string $enableBankAccountGenerator
     */
    public function setEnableBankAccountGenerator($enableBankAccountGenerator)
    {
        $this->enableBankAccountGenerator = $enableBankAccountGenerator;

        return $this;
    }

    /**
     * @return string
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param string $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return string
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * @param string $bankName
     */
    public function setBankName($bankName)
    {
        $this->bankName = $bankName;

        return $this;
    }

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

        return $this;
    }


    /**
     * @return string
     */
    public function getNip()
    {
        return $this->nip;
    }

    /**
     * @param string $nip
     */
    public function setNip($nip)
    {
        $this->nip = $nip;

        return $this;
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

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * @param string $zipcode
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;

        return $this;
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

        return $this;
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
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Company
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Company
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
     * @return Company
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

    public function __toString()
    {
        return $this->title;
    }
}

