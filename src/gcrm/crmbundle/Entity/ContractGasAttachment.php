<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * ContractAttachment
 *
 * @ORM\Table(name="contract_gas_attachment")
 * @Vich\Uploadable
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ContractGasAttachmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ContractGasAttachment
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\ContractGas", inversedBy="contractAttachments")
     * @ORM\JoinColumn(name="contract_gas_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $contract;

    /**
     * @var string
     *
     * @ORM\Column(name="urlFileTemp", type="string", length=255, nullable=true)
     */
    private $urlFileTemp;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="contractsGasPrivate", fileNameProperty="urlFileTemp")
     */
    private $urlFile;

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
     * @ORM\Column(name="register_number", type="string", length=255, nullable=true)
     */
    private $registerNumber;

    /**
     * @var string
     * 
     * @ORM\Column(name="box_number", type="string", length=255, nullable=true)
     */
    private $boxNumber;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getContract()
    {
        return $this->contract;
    }

    public function setContract(ContractGas $contract)
    {
        $this->contract = $contract;
    }

    /**
     * Set urlFileTemp
     *
     * @param string $urlFileTemp
     *
     * @return ContractAttachment
     */
    public function setUrlFileTemp($urlFileTemp)
    {
        $this->urlFileTemp = $urlFileTemp;

        return $this;
    }

    /**
     * Get urlFileTemp
     *
     * @return string
     */
    public function getUrlFileTemp()
    {
        return $this->urlFileTemp;
    }

    public function setUrlFile($file = null)
    {
        $this->urlFile = $file;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($file) {
            // if 'updatedAt' is not defined in your entity, use another property
            $this->updatedAt = new \DateTime('now');
        } else {
            $this->urlFileTemp = '';
        }
    }

    public function getUrlFile()
    {
        return $this->urlFile;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return ContractAttachment
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
     * @return ContractAttachment
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
     * Set registerNumber
     * 
     * @param string $number
     * @return ContractEnergyAttachment
     */
    public function setRegisterNumber($number)
    {
        $this->registerNumber = $number;
        return $this;
    }

    /**
     * Get registerNumber
     * 
     * @return string
     */
    public function getRegisterNumber()
    {
        return $this->registerNumber;
    }

    /**
     * Set boxNumber
     * 
     * @param string $number
     * @return ContractEnergyAttachment
     */
    public function setBoxNumber($number)
    {
        $this->boxNumber = $number;
        return $this;
    }

    /**
     * Get boxNumber
     * 
     * @return ContractEnergyAttachment
     */
    public function getBoxNumber()
    {
        return $this->boxNumber;
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

