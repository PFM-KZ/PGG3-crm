<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateModel;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateRecordModel;

/**
 * DocumentPackageToGenerate
 *
 * @ORM\Table(name="document_package_to_generate")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\DocumentPackageToGenerateRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DocumentPackageToGenerate
{
    /**
     * @var integer
     *
     * @ORM\Column(name="generated_document_entity", type="integer", nullable=true)
     */
    private $generatedDocumentEntity;

    /**
     * @return mixed
     */
    public function getGeneratedDocumentEntity()
    {
        return $this->generatedDocumentEntity;
    }

    /**
     * @param mixed $generatedDocumentEntity
     */
    public function setGeneratedDocumentEntity($generatedDocumentEntity)
    {
        $this->generatedDocumentEntity = $generatedDocumentEntity;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="document_entity", type="integer")
     */
    private $documentEntity;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer")
     */
    private $type;

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="params", type="text", nullable=true)
     */
    private $params;

    /**
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return mixed
     */
    public function getDocumentEntity()
    {
        return $this->documentEntity;
    }

    /**
     * @param mixed $documentEntity
     */
    public function setDocumentEntity($documentEntity)
    {
        $this->documentEntity = $documentEntity;
    }

    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\DocumentPackageToGenerateRecord", mappedBy="package", cascade={"remove"})
     */
    private $packageRecords;

    /**
     * @return mixed
     */
    public function getPackageRecords()
    {
        return $this->packageRecords;
    }

    public function getCountWaitingToProcess()
    {
        $count = 0;
        /** @var DocumentPackageToGenerateRecord $packageRecord */
        foreach ($this->packageRecords as $packageRecord) {
            if ($packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_WAITING_TO_PROCESS) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountToProcess()
    {
        $count = 0;
        /** @var DocumentPackageToGenerateRecord $packageRecord */
        foreach ($this->packageRecords as $packageRecord) {
            if ($packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_IN_PROCESS) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountWaitingToGenerate()
    {
        $count = 0;
        /** @var DocumentPackageToGenerateRecord $packageRecord */
        foreach ($this->packageRecords as $packageRecord) {
            if ($packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_WAITING_TO_GENERATE) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountToGenerate()
    {
        $count = 0;
        /** @var DocumentPackageToGenerateRecord $packageRecord */
        foreach ($this->packageRecords as $packageRecord) {
            if ($packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_GENERATE) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountCompleted()
    {
        $count = 0;
        /** @var DocumentPackageToGenerateRecord $packageRecord */
        foreach ($this->packageRecords as $packageRecord) {
            if ($packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_COMPLETE) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountError()
    {
        $count = 0;
        /** @var DocumentPackageToGenerateRecord $packageRecord */
        foreach ($this->packageRecords as $packageRecord) {
            if (
                $packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_PROCESS_ERROR ||
                $packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_GENERATE_ERROR
            ) {
                $count++;
            }
        }

        return $count;
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
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime")
     */
    private $createdDate;

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
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\CustomDocumentTemplate")
     * @ORM\JoinColumn(name="custom_document_template_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $customDocumentTemplate;

    /**
     * @return mixed
     */
    public function getCustomDocumentTemplate()
    {
        return $this->customDocumentTemplate;
    }

    /**
     * @param mixed $customDocumentTemplate
     */
    public function setCustomDocumentTemplate($customDocumentTemplate)
    {
        $this->customDocumentTemplate = $customDocumentTemplate;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $addedBy;

    /**
     * @return mixed
     */
    public function getAddedBy()
    {
        return $this->addedBy;
    }

    /**
     * @param mixed $addedBy
     */
    public function setAddedBy($addedBy)
    {
        $this->addedBy = $addedBy;
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
     * @return DocumentPackageToGenerate
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
     * @return DocumentPackageToGenerate
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
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTime());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());
        }

        if ($this->getCreatedDate() == null) {
            $this->setCreatedDate(new \DateTime());
        }
    }
}

