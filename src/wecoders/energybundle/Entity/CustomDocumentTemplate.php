<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Service\DebitNotePackageRecordModel;

/**
 * CustomDocumentTemplate
 *
 * @ORM\Table(name="custom_document_template")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\CustomDocumentTemplateRepository")
 * @ORM\HasLifecycleCallbacks
 */
class CustomDocumentTemplate
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
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\CustomDocumentTemplateAndDocument", mappedBy="customDocumentTemplate", cascade={"persist","remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"position" = "ASC"})
     */
    private $customDocumentTemplateAndDocuments;

    public function addCustomDocumentTemplateAndDocument(CustomDocumentTemplateAndDocument $customDocumentTemplateAndDocument)
    {
        $this->customDocumentTemplateAndDocuments[] = $customDocumentTemplateAndDocument;
        $customDocumentTemplateAndDocument->setCustomDocumentTemplate($this);

        return $this;
    }

    public function removeCustomDocumentTemplateAndDocument(CustomDocumentTemplateAndDocument $customDocumentTemplateAndDocument)
    {
        $this->customDocumentTemplateAndDocuments->removeElement($customDocumentTemplateAndDocument);
    }

    public function getCustomDocumentTemplateAndDocuments()
    {
        return $this->customDocumentTemplateAndDocuments;
    }

    public function setCustomDocumentTemplateAndDocuments($customDocumentTemplateAndDocuments)
    {
        $this->customDocumentTemplateAndDocuments = $customDocumentTemplateAndDocuments;
    }

    public function __toString()
    {
        return $this->title;
    }

}

