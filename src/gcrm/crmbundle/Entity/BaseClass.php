<?php

namespace GCRM\CRMBundle\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 */
class BaseClass
{

    /**
     * @var \DateTime
     * 
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

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
     * Set createdAt
     * 
     * @param \DateTime $createdAt
     * 
     * @return BaseClass
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @var \DateTime
     * 
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updatedAt;

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
     * Set updatedAt
     * 
     * @param \DateTime $updatedAt
     * 
     * @return BaseClass
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @var boolean
     * 
     * @ORM\Column(type="boolean", options={"default": 0})
     */
    protected $deleted;

    /**
     * Get deleted
     * 
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set deleted
     * 
     * @param boolean $deleted
     * 
     * @return BaseClass
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * @var User
     * 
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="added_by_user", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $addedBy;

    /**
     * Get addedBy
     * 
     * @return User
     */
    public function getAddedBy()
    {
        return $this->addedBy;
    }

    /**
     * Set addedBy
     * 
     * @param User $user
     * 
     * @return BaseClass
     */
    public function setAddedBy($user)
    {
        $this->addedBy = $user;
        return $this;
    }

    /**
     * Update createdAt and/or updatedAt accordingly
     * 
     * @ORM\PrePersist,
     * @ORM\PreUpdate
     */
    public function updateDates()
    {
        if(!$this->createdAt) {
            $this->createdAt = new \DateTime();
        } else {
            $this->updatedAt = new \DateTime();
        }
    }
}