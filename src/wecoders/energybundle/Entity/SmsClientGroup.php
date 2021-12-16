<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use TZiebura\SmsBundle\Entity\SmsClientGroup as BaseSmsClientGroup;

/**
 * @ORM\Table(name="sms_client_group")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\SmsClientGroupRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SmsClientGroup extends BaseSmsClientGroup
{
    const GROUP_PRE_PAYMENT_SMS = 0;
    const GROUP_POST_PAYMENT_SMS = 1;
    const GROUP_WELCOME_SMS = 2;
    const GROUP_CUSTOM_DATE_OF_PAYMENT_SMS = 3;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @ORM\Column(name="code", type="smallint")
     */
    private $code;

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @ORM\Column(name="total_to_send_count", type="integer")
     */
    private $totalToSendCount;

    public function getTotalToSendCount()
    {
        return $this->totalToSendCount;
    }

    /**
     * @ORM\Column(name="sent_count", type="integer")
     */
    private $sentCount;

    public function getSentCount()
    {
        return $this->sentCount;
    }

    public function setSentCount($sentCount)
    {
        $this->sentCount = $sentCount;
        return $this;
    }

    /**
     * @ORM\Column(name="error_count", type="integer")
     */
    private $errorCount;

    public function getErrorCount()
    {
        return $this->errorCount;
    }

    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;
        return $this;
    }

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($date)
    {
        $this->createdAt = $date;
        return $this;
    }

    /**
     * @ORM\Column(name="completed_at", type="datetime", nullable=true)
     */
    private $completedAt;

    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    public function setCompletedAt($completedAt)
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    /**
     * @ORM\Column(name="is_suspended", type="boolean")
     */
    private $isSuspended;

    public function getIsSuspended()
    {
        return $this->isSuspended;
    }

    public function setIsSuspended($isSuspended)
    {
        if($this->statusCode == self::STATUS_COMPLETED) {
            return $this;
        }

        if($isSuspended) {
            $this->statusCode = self::STATUS_SUSPENDED;
        } else {
            $this->statusCode = self::STATUS_AWAITING;
        }
        $this->isSuspended = $isSuspended;
        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function updateTimestamps()
    {
        if(!$this->createdAt) {
            $this->createdAt = new \DateTime();
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTotalToSendCount()
    {
        $this->totalToSendCount = count($this->smsMessages);
    }

    public function __toString()
    {
        return $this->title;
    }

    function __construct()
    {
        $this->sentCount = 0;
        $this->errorCount = 0;
        $this->isSuspended = false;
        parent::__construct();
    }
}
