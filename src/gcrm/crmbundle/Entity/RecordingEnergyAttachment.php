<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * RecordingEnergyAttachment
 *
 * @ORM\Table(name="recording_energy_attachment")
 * @Vich\Uploadable
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\RecordingEnergyAttachmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class RecordingEnergyAttachment
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\ContractEnergy", inversedBy="recordingAttachments")
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
     * @Vich\UploadableField(mapping="recordingEnergyPrivate", fileNameProperty="urlFileTemp")
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

    public function setContract(ContractEnergy $contract)
    {
        $this->contract = $contract;
    }

    /**
     * Set urlFileTemp
     *
     * @param string $urlFileTemp
     *
     * @return RecordingEnergyAttachment
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
     * @return RecordingEnergyAttachment
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
     * @return RecordingEnergyAttachment
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

