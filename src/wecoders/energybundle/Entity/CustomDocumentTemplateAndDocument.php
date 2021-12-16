<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Service\DebitNotePackageRecordModel;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * CustomDocumentTemplateAndDocument
 *
 * @ORM\Table(name="custom_document_template_and_document")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\CustomDocumentTemplateRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class CustomDocumentTemplateAndDocument
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\CustomDocumentTemplate", inversedBy="customDocumentTemplateAndDocuments")
     * @ORM\JoinColumn(name="custom_document_template_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $customDocumentTemplate;

    /**
     * @return mixed
     */
    public function getCustomDocumentTemplate()
    {
        return $this->customDocumentTemplate;
    }

    /**
     * @param mixed $customDocumentTemplate
     */
    public function setCustomDocumentTemplate($customDocumentTemplate)
    {
        $this->customDocumentTemplate = $customDocumentTemplate;
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

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
     * @var integer
     *
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    private $position;

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="file_path", type="string", length=255, nullable=true)
     */
    private $filePath;

    /**
     * @var File
     *
     * @Vich\UploadableField(mapping="customDocumentTemplateAndDocument", fileNameProperty="filePath")
     */
    private $file;

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
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Set filePath
     *
     * @param string $filePath
     *
     * @return CustomDocumentTemplateAndDocument
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    public function setFile($file = null)
    {
        $this->file = $file;

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

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTime());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());
        }
    }
}

