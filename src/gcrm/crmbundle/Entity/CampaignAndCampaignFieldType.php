<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CampaignAndCampaignFieldType
 *
 * @ORM\Table(name="campaign_and_campaign_field_type")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\CampaignAndCampaignFieldTypeRepository")
 * @ORM\HasLifecycleCallbacks
 */
class CampaignAndCampaignFieldType
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Campaign", inversedBy="campaignAndCampaignFieldTypes")
     * @ORM\JoinColumn(name="campaign_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $campaign;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\CampaignFieldType")
     * @ORM\JoinColumn(name="campaign_field_type_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $campaignFieldType;

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

    public function getCampaign()
    {
        return $this->campaign;
    }

    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getCampaignFieldType()
    {
        return $this->campaignFieldType;
    }

    public function setCampaignFieldType($campaignFieldType)
    {
        $this->campaignFieldType = $campaignFieldType;

        return $this;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return CampaignAndCampaignFieldType
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
     * @return CampaignAndCampaignFieldType
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

    public function __toString()
    {
        return $this->campaign->getTitle();
    }
}

