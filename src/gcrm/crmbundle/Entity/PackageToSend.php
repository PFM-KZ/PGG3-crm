<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PackageToSend
 *
 * @ORM\Table(name="package_to_send")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\PackageToSendRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PackageToSend
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
     * @ORM\Column(name="contract_ids", type="text")
     */
    private $contractIds;

    /**
     * @var string
     *
     * @ORM\Column(name="checked_ids_good", type="text", nullable=true)
     */
    private $checkedIdsGood;

    /**
     * @var string
     *
     * @ORM\Column(name="checked_ids_bad", type="text", nullable=true)
     */
    private $checkedIdsBad;

    /**
     * @return string
     */
    public function getCheckedIdsGood()
    {
        return explode(',', $this->checkedIdsGood);
    }

    /**
     * @param string $checkedIdsGood
     */
    public function setCheckedIdsGood($checkedIdsGood)
    {
        if (is_array($checkedIdsGood)) {
            $this->checkedIdsGood = implode(',', $checkedIdsGood);
        } else {
            $this->checkedIdsGood = $checkedIdsGood;
        }
    }

    /**
     * @return string
     */
    public function getCheckedIdsBad()
    {
        return explode(',', $this->checkedIdsBad);
    }

    /**
     * @param string $checkedIdsBad
     */
    public function setCheckedIdsBad($checkedIdsBad)
    {
        if (is_array($checkedIdsBad)) {
            $this->checkedIdsBad = implode(',', $checkedIdsBad);
        } else {
            $this->checkedIdsBad = $checkedIdsBad;
        }
    }

    /**
     * @var string
     *
     * @ORM\Column(name="checked_contract_ids", type="text", nullable=true)
     */
    private $checkedContractIds;

    /**
     * @return string
     */
    public function getCheckedContractIds()
    {
        return $this->checkedContractIds;
    }

    /**
     * @param string $checkedContractIds
     */
    public function setCheckedContractIds($checkedContractIds)
    {
        $this->checkedContractIds = $checkedContractIds;

        return $this;
    }

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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Branch")
     * @ORM\JoinColumn(name="origin_branch_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $originBranch;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Branch")
     * @ORM\JoinColumn(name="from_branch_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $fromBranch;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Branch")
     * @ORM\JoinColumn(name="to_branch_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $toBranch;

    /**
     * @return mixed
     */
    public function getOriginBranch()
    {
        return $this->originBranch;
    }

    /**
     * @param mixed $originBranch
     */
    public function setOriginBranch($originBranch)
    {
        $this->originBranch = $originBranch;
    }

    /**
     * @return mixed
     */
    public function getFromBranch()
    {
        return $this->fromBranch;
    }

    /**
     * @param mixed $fromBranch
     */
    public function setFromBranch($fromBranch)
    {
        $this->fromBranch = $fromBranch;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getToBranch()
    {
        return $this->toBranch;
    }

    /**
     * @param mixed $toBranch
     */
    public function setToBranch($toBranch)
    {
        $this->toBranch = $toBranch;

        return $this;
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="is_returned", type="boolean", options={"default": 0}, nullable=true)
     */
    private $isReturned;

    /**
     * @return bool
     */
    public function getIsReturned()
    {
        return $this->isReturned;
    }

    /**
     * @param bool $isReturned
     */
    public function setIsReturned($isReturned)
    {
        $this->isReturned = $isReturned;

        return $this;
    }

    /**
     * @var bool
     *
     * @ORM\Column(name="is_processed", type="boolean", options={"default": 0})
     */
    private $isProcessed;

    /**
     * @return bool
     */
    public function getIsProcessed()
    {
        return $this->isProcessed;
    }

    /**
     * @param bool $isProcessed
     */
    public function setIsProcessed($isProcessed)
    {
        $this->isProcessed = $isProcessed;

        return $this;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="returned_comment", type="text", nullable=true)
     */
    private $returnedComment;

    /**
     * @return string
     */
    public function getReturnedComment()
    {
        return $this->returnedComment;
    }

    /**
     * @param string $returnedComment
     */
    public function setReturnedComment($returnedComment)
    {
        $this->returnedComment = $returnedComment;

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
     * Set number
     *
     * @param string $number
     *
     * @return PackageToSend
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
     * @return PackageToSend
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
     * @return PackageToSend
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
     * @return PackageToSend
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
     * @return PackageToSend
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

