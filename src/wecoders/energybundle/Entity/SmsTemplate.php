<?php

namespace Wecoders\EnergyBundle\Entity;

use TZiebura\SmsBundle\Entity\SmsTemplate as BaseSmsTemplate;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sms_template")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\SmsTemplateRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SmsTemplate extends BaseSmsTemplate
{
    const PRE_PAYMENT_TEMPLATE = 0;
    const POST_PAYMENT_TEMPLATE = 1;
    const WELCOME_TEMPLATE = 2;
    const CUSTOM_DATE_OF_PAYMENT_TEMPLATE = 3;
    const BANK_ACCOUNT_CHANGE = 4;

    /**
     * @ORM\Column(name="created_at", type="datetime")
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
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updateTimestamps()
    {
        if(!$this->createdAt) {
            $this->createdAt = new \DateTime();
        } else {
            $this->updatedAt = new \DateTime();
        }
    }
}