<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Service\SettlementPackageRecordModel;

/**
 * SettlementPackage
 *
 * @ORM\Table(name="settlement_package")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\PackageToProcessRepository")
 * @ORM\HasLifecycleCallbacks
 */
class SettlementPackage
{

    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\SettlementPackageRecord", mappedBy="settlementPackage", cascade={"remove"})
     */
    private $settlementPackageRecords;

    /**
     * @return mixed
     */
    public function getSettlementPackageRecords()
    {
        return $this->settlementPackageRecords;
    }

    public function getCountWaitingToProcess()
    {
        $count = 0;
        /** @var SettlementPackageRecord $settlementPackageRecord */
        foreach ($this->settlementPackageRecords as $settlementPackageRecord) {
            if ($settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_WAITING_TO_PROCESS) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountToProcess()
    {
        $count = 0;
        /** @var SettlementPackageRecord $settlementPackageRecord */
        foreach ($this->settlementPackageRecords as $settlementPackageRecord) {
            if ($settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_IN_PROCESS) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountWaitingToGenerate()
    {
        $count = 0;
        /** @var SettlementPackageRecord $settlementPackageRecord */
        foreach ($this->settlementPackageRecords as $settlementPackageRecord) {
            if ($settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_WAITING_TO_GENERATE) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountToGenerate()
    {
        $count = 0;
        /** @var SettlementPackageRecord $settlementPackageRecord */
        foreach ($this->settlementPackageRecords as $settlementPackageRecord) {
            if ($settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_GENERATE) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountCompleted()
    {
        $count = 0;
        /** @var SettlementPackageRecord $settlementPackageRecord */
        foreach ($this->settlementPackageRecords as $settlementPackageRecord) {
            if ($settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_COMPLETE) {
                $count++;
            }
        }

        return $count;
    }

    public function getCountError()
    {
        $count = 0;
        /** @var SettlementPackageRecord $settlementPackageRecord */
        foreach ($this->settlementPackageRecords as $settlementPackageRecord) {
            if (
                $settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_PROCESS_ERROR ||
                $settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_GENERATE_ERROR
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
     * @return SettlementPackage
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
     * @return SettlementPackage
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

