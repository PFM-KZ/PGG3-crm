<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PackageToProcess
 *
 * @ORM\Table(name="package_to_process")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\PackageToProcessRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PackageToProcess
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
     * @ORM\Column(name="number", type="string", length=255)
     */
    private $number;

    /**
     * @var string
     *
     * @ORM\Column(name="contractIds", type="text")
     */
    private $contractIds;

    /**
     * @var string
     *
     * @ORM\Column(name="contractType", type="string", length=255)
     */
    private $contractType;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $addedBy;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="cancelled_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $cancelledBy;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_cancelled", type="boolean", options={"default": 0})
     */
    private $isCancelled;

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
     * @return mixed
     */
    public function getCancelledBy()
    {
        return $this->cancelledBy;
    }

    /**
     * @param mixed $cancelledBy
     */
    public function setCancelledBy($cancelledBy)
    {
        $this->cancelledBy = $cancelledBy;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsCancelled()
    {
        return $this->isCancelled;
    }

    /**
     * @param bool $isCancelled
     */
    public function setIsCancelled($isCancelled)
    {
        $this->isCancelled = $isCancelled;

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
     * Set number
     *
     * @param string $number
     *
     * @return PackageToProcess
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set contractIds
     *
     * @param string $contractIds
     *
     * @return PackageToProcess
     */
    public function setContractIds($contractIds)
    {
        if (!is_array($contractIds) || (is_array($contractIds) && !count($contractIds))) {
            $this->contractIds = null;
            return $this;
        }

        $this->contractIds = implode(',', $contractIds);

        return $this;
    }

    /**
     * Get contractIds
     *
     * @return string
     */
    public function getContractIds()
    {
        return $this->contractIds;
    }

    /**
     * Set contractType
     *
     * @param string $contractType
     *
     * @return PackageToProcess
     */
    public function setContractType($contractType)
    {
        $this->contractType = $contractType;

        return $this;
    }

    /**
     * Get contractType
     *
     * @return string
     */
    public function getContractType()
    {
        return $this->contractType;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return PackageToProcess
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
     * @return PackageToProcess
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

