<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Payment
 *
 * @ORM\Table(name="payment")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\PaymentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Payment
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
     * @ORM\Column(name="badge_id", type="string", length=12)
     */
    private $badgeId;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="decimal", precision=10, scale=2)
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_branch_number", type="string", length=8, nullable=true)
     */
    private $senderBranchNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_branch_number", type="string", length=8, nullable=true)
     */
    private $receiverBranchNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_account_number", type="string", length=26)
     */
    private $senderAccountNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_account_number", type="string", length=26, nullable=true)
     */
    private $receiverAccountNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="sender_name", type="string", length=255)
     */
    private $senderName;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_name", type="string", length=255, nullable=true)
     */
    private $receiverName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="data", type="text")
     */
    private $data;

    /**
     * @ORM\Column(name="help", type="text", nullable=true)
     */
    private $help;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="integer", nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255, nullable=true)
     */
    private $filename;

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return mixed
     */
    public function getHelp()
    {
        return $this->help;
    }

    /**
     * @param mixed $help
     */
    public function setHelp($help)
    {
        $this->help = $help;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
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
     * @return string
     */
    public function getBadgeId()
    {
        return $this->badgeId;
    }

    /**
     * @param string $badgeId
     */
    public function setBadgeId($badgeId)
    {
        $this->badgeId = $badgeId;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getSenderBranchNumber()
    {
        return $this->senderBranchNumber;
    }

    /**
     * @param string $senderBranchNumber
     */
    public function setSenderBranchNumber($senderBranchNumber)
    {
        $this->senderBranchNumber = $senderBranchNumber;
    }

    /**
     * @return string
     */
    public function getReceiverBranchNumber()
    {
        return $this->receiverBranchNumber;
    }

    /**
     * @param string $receiverBranchNumber
     */
    public function setReceiverBranchNumber($receiverBranchNumber)
    {
        $this->receiverBranchNumber = $receiverBranchNumber;
    }

    /**
     * @return string
     */
    public function getSenderAccountNumber()
    {
        return $this->senderAccountNumber;
    }

    /**
     * @param string $senderAccountNumber
     */
    public function setSenderAccountNumber($senderAccountNumber)
    {
        $this->senderAccountNumber = $senderAccountNumber;
    }

    /**
     * @return string
     */
    public function getReceiverAccountNumber()
    {
        return $this->receiverAccountNumber;
    }

    /**
     * @param string $receiverAccountNumber
     */
    public function setReceiverAccountNumber($receiverAccountNumber)
    {
        $this->receiverAccountNumber = $receiverAccountNumber;
    }

    /**
     * @return string
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * @param string $senderName
     */
    public function setSenderName($senderName)
    {
        $this->senderName = $senderName;
    }

    /**
     * @return string
     */
    public function getReceiverName()
    {
        return $this->receiverName;
    }

    /**
     * @param string $receiverName
     */
    public function setReceiverName($receiverName)
    {
        $this->receiverName = $receiverName;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return \DateTime
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param \DateTime $data
     */
    public function setData($data)
    {
        $this->data = $data;
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
            $this->setCreatedAt(new \DateTime('now'));
        }
    }

    public function __toString()
    {
        return $this->id;
    }
}

