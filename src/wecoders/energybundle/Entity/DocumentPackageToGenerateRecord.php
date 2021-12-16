<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentPackageToGenerateRecord
 *
 * @ORM\Table(name="document_package_to_generate_record")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\DocumentPackageToGenerateRecordRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DocumentPackageToGenerateRecord
{
    /**
     * @var integer
     *
     * @ORM\Column(name="generated_document_entity", type="integer", nullable=true)
     */
    private $generatedDocumentEntity;

    /**
     * @return mixed
     */
    public function getGeneratedDocumentEntity()
    {
        return $this->generatedDocumentEntity;
    }

    /**
     * @param mixed $generatedDocumentEntity
     */
    public function setGeneratedDocumentEntity($generatedDocumentEntity)
    {
        $this->generatedDocumentEntity = $generatedDocumentEntity;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\DocumentPackageToGenerate", inversedBy="packageRecords")
     * @ORM\JoinColumn(name="package_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $package;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $client;

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param mixed $package
     */
    public function setPackage($package)
    {
        $this->package = $package;
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
     * @var string
     *
     * @ORM\Column(name="pp", type="string", length=255, nullable=true)
     */
    private $pp;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_from", type="datetime", nullable=true)
     */
    private $dateFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_to", type="datetime", nullable=true)
     */
    private $dateTo;

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
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error_message", type="text", nullable=true)
     */
    private $errorMessage;

    /**
     * @var integer
     *
     * @ORM\Column(name="document_id", type="integer", nullable=true)
     */
    private $documentId;

    /**
     * @var integer
     *
     * @ORM\Column(name="generated_document_id", type="integer", nullable=true)
     */
    private $generatedDocumentId;

    /**
     * @return int
     */
    public function getGeneratedDocumentId()
    {
        return $this->generatedDocumentId;
    }

    /**
     * @param int $generatedDocumentId
     */
    public function setGeneratedDocumentId($generatedDocumentId)
    {
        $this->generatedDocumentId = $generatedDocumentId;
    }

    /**
     * @return mixed
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @param mixed $documentId
     */
    public function setDocumentId($documentId)
    {
        $this->documentId = $documentId;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

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
    public function getPp()
    {
        return $this->pp;
    }

    /**
     * @param string $pp
     */
    public function setPp($pp)
    {
        $this->pp = $pp;
    }

    /**
     * @return \DateTime
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @param \DateTime $dateFrom
     */
    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;
    }

    /**
     * @return \DateTime
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @param \DateTime $dateTo
     */
    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return DocumentPackageToGenerateRecord
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
     * @return DocumentPackageToGenerateRecord
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
        $this->setUpdatedAt(new \DateTime());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());
        }
    }
}

