<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentRequestPackageToGenerate
 *
 * @ORM\Table(name="payment_request_package_to_generate")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\PaymentRequestRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PaymentRequestPackageToGenerate
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
     * @ORM\Column(name="object_ids", type="text")
     */
    private $objectIds;

    public function getObjectsCount()
    {
        return count($this->getObjectIds());
    }

    /**
     * @var string
     *
     * @ORM\Column(name="checked_object_ids", type="text", nullable=true)
     */
    private $checkedObjectIds;

    public function getCheckedObjectsCount()
    {
        return count($this->getCheckedObjectIds());
    }

    /**
     * @var string
     *
     * @ORM\Column(name="document_ids", type="text", nullable=true)
     */
    private $documentIds;

    public function getDocumentsCount()
    {
        return count($this->getDocumentIds());
    }

    /**
     * @var string
     *
     * @ORM\Column(name="checked_document_ids", type="text", nullable=true)
     */
    private $checkedDocumentIds;

    public function getCheckedDocumentsCount()
    {
        return count($this->getCheckedDocumentIds());
    }

    /**
     * @return array
     */
    public function getDocumentIds()
    {
        return array_filter(explode(',', $this->documentIds));
    }

    /**
     * @param string $documentIds
     */
    public function setDocumentIds($documentIds)
    {
        if (!is_array($documentIds) || (is_array($documentIds) && !count($documentIds))) {
            $this->documentIds = null;
            return $this;
        }
        $this->documentIds = implode(',', $documentIds);

        return $this;
    }

    public function addDocumentId($documentId)
    {
        $documentIds = $this->getDocumentIds();
        if (!in_array($documentId, $documentIds)) {
            $documentIds[] = $documentId;
            $this->setDocumentIds($documentIds);
        }
    }

    /**
     * @return array
     */
    public function getCheckedDocumentIds()
    {
        return array_filter(explode(',', $this->checkedDocumentIds));
    }

    /**
     * @param string $checkedDocumentIds
     */
    public function setCheckedDocumentIds($checkedDocumentIds)
    {
        if (!is_array($checkedDocumentIds) || (is_array($checkedDocumentIds) && !count($checkedDocumentIds))) {
            $this->checkedDocumentIds = null;
            return $this;
        }
        $this->checkedDocumentIds = implode(',', $checkedDocumentIds);

        return $this;
    }

    /**
     * @return array
     */
    public function getCheckedObjectIds()
    {
        return array_filter(explode(',', $this->checkedObjectIds));
    }

    /**
     * @param string $checkedObjectIds
     */
    public function setCheckedObjectIds($checkedObjectIds)
    {
        if (!is_array($checkedObjectIds) || (is_array($checkedObjectIds) && !count($checkedObjectIds))) {
            $this->checkedObjectIds = null;
            return $this;
        }
        $this->checkedObjectIds = implode(',', $checkedObjectIds);

        return $this;
    }

    public function addCheckedObjectId($checkedObjectId)
    {
        $checkedObjectIds = $this->getCheckedObjectIds();
        if (!in_array($checkedObjectId, $checkedObjectIds)) {
            $checkedObjectIds[] = $checkedObjectId;
            $this->setCheckedObjectIds($checkedObjectIds);
        }
    }

    public function addCheckedDocumentId($checkedDocumentId)
    {
        $checkedDocumentIds = $this->getCheckedDocumentIds();
        if (!in_array($checkedDocumentId, $checkedDocumentIds)) {
            $checkedDocumentIds[] = $checkedDocumentId;
            $this->setCheckedDocumentIds($checkedDocumentIds);
        }
    }

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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $addedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime", nullable=true)
     */
    private $createdDate;

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
     * Set objectIds
     *
     * @param string $objectIds
     *
     * @return PaymentRequestPackageToGenerate
     */
    public function setObjectIds($objectIds)
    {
        if (!is_array($objectIds) || (is_array($objectIds) && !count($objectIds))) {
            $this->objectIds = null;
            return $this;
        }

        $this->objectIds = implode(',', $objectIds);

        return $this;
    }

    /**
     * Get objectIds
     *
     * @return array
     */
    public function getObjectIds()
    {
        return array_filter(explode(',', $this->objectIds));
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return PaymentRequestPackageToGenerate
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
     * @return PaymentRequestPackageToGenerate
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
     * Set addedBy
     */
    public function setAddedBy($addedBy)
    {
        $this->addedBy = $addedBy;

        return $this;
    }

    /**
     * Get addedBy
     */
    public function getAddedBy()
    {
        return $this->addedBy;
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

