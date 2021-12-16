<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * ClientEnquiryAttachment
 *
 * @ORM\Table(name="client_enquiry_attachment")
 * @Vich\Uploadable
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 */
class ClientEnquiryAttachment
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\ClientEnquiry", inversedBy="enquiryAttachments")
     */
    private $clientEnquiry;

    /**
     * @var string
     *
     * @ORM\Column(name="url_file_temp", type="string", length=255, nullable=true)
     */
    private $urlFileTemp;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="clientEnquiryFile", fileNameProperty="urlFileTemp")
     */
    private $urlFile;

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

    public function getClientEnquiry()
    {
        return $this->clientEnquiry;
    }

    public function setClientEnquiry(ClientEnquiry $clientEnquiry)
    {
        $this->clientEnquiry = $clientEnquiry;
    }

    /**
     * Set urlFileTemp
     *
     * @param string $urlFileTemp
     *
     * @return ClientEnquiryAttachment
     */
    public function setUrlFileTemp($urlFileTemp)
    {
        $this->urlFileTemp = $urlFileTemp;

        return $this;
    }

    /**
     * Get urlFileTemp
     *
     * @return string
     */
    public function getUrlFileTemp()
    {
        return $this->urlFileTemp;
    }

    public function setUrlFile($file = null)
    {
        $this->urlFile = $file;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($file) {
            // if 'updatedAt' is not defined in your entity, use another property
            $this->updatedAt = new \DateTime();
        } else {
            $this->urlFileTemp = '';
        }
    }

    public function getUrlFile()
    {
        return $this->urlFile;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return ClientEnquiryAttachment
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
     * @return ClientEnquiryAttachment
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
}

