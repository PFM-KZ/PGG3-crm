<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChangeStatusLog
 *
 * @ORM\Table(name="change_status_log")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ChangeStatusLogRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ChangeStatusLog
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
     * @ORM\Column(name="contract_number", type="string", length=255, nullable=true)
     */
    private $contractNumber;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
     * @ORM\JoinColumn(name="from_status_contract_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $fromStatus;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
     * @ORM\JoinColumn(name="to_status_contract_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $toStatus;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusDepartment")
     * @ORM\JoinColumn(name="status_department_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $department;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="changed_by_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $changedBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

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
     * @return mixed
     */
    public function getContractNumber()
    {
        return $this->contractNumber;
    }

    /**
     * @param mixed $contractNumber
     */
    public function setContractNumber($contractNumber)
    {
        $this->contractNumber = $contractNumber;
    }

    /**
     * @return mixed
     */
    public function getFromStatus()
    {
        return $this->fromStatus;
    }

    /**
     * @param mixed $fromStatus
     */
    public function setFromStatus($fromStatus)
    {
        $this->fromStatus = $fromStatus;
    }

    /**
     * @return mixed
     */
    public function getToStatus()
    {
        return $this->toStatus;
    }

    /**
     * @param mixed $toStatus
     */
    public function setToStatus($toStatus)
    {
        $this->toStatus = $toStatus;
    }

    /**
     * @return mixed
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param mixed $department
     */
    public function setDepartment($department)
    {
        $this->department = $department;
    }

    /**
     * @return mixed
     */
    public function getChangedBy()
    {
        return $this->changedBy;
    }

    /**
     * @param mixed $changedBy
     */
    public function setChangedBy($changedBy)
    {
        $this->changedBy = $changedBy;
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
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());
        }
    }
}

