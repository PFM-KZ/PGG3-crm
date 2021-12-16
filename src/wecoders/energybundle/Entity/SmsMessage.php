<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use TZiebura\SmsBundle\Entity\SmsMessage as BaseSmsMessage;

/**
 * @ORM\Table(name="sms_message")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\SmsMessageRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SmsMessage extends BaseSmsMessage
{
    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Client")
     */
    private $client;

    public function getClient()
    {
        return $this->client;
    }

    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @ORM\Column(name="document_numbers", type="text", nullable=true)
     */
    private $documentNumbers;

    public function getDocumentNumbers()
    {
        return $this->documentNumbers;
    }

    public function setDocumentNumbers($documentNumbers)
    {
        $this->documentNumbers = $documentNumbers;
        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function updateTimestamps()
    {
        $this->createdAt = new \DateTime();
    }
}