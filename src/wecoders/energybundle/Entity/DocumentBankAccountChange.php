<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentBankAccountChange
 *
 * @ORM\Table(name="document_bank_account_change")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\DocumentBankAccountChangeRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DocumentBankAccountChange
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
     * @ORM\Column(name="document_number", type="string", length=255, nullable=true)
     */
    private $documentNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="badge_id", type="string", length=12, unique=true)
     */
    private $badgeId;

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
     * @var string
     *
     * @ORM\Column(name="file_path", type="string", length=255, nullable=true)
     */
    private $filePath;

    /**
     * Set filePath
     *
     * @param string $filePath
     *
     * @return DocumentBankAccountChange
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

    /**
     * @return string
     */
    public function getDocumentNumber()
    {
        return $this->documentNumber;
    }

    /**
     * @param string $documentNumber
     */
    public function setDocumentNumber($documentNumber)
    {
        $this->documentNumber = $documentNumber;
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

}

