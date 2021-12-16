<?php

namespace Wecoders\EnergyBundle\Entity\DocumentTable;

use Doctrine\ORM\Mapping as ORM;

/**
 * TableHeading
 *
 * @ORM\Table(name="table_heading")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\DocumentTableRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TableHeading
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
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\DocumentTable\DocumentTable", inversedBy="tableHeadings")
     * @ORM\JoinColumn(name="document_table_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $documentTable;

    /**
     * @return mixed
     */
    public function getDocumentTable()
    {
        return $this->documentTable;
    }

    /**
     * @param mixed $documentTable
     */
    public function setDocumentTable($documentTable)
    {
        $this->documentTable = $documentTable;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=255, nullable=true)
     */
    protected $text;

    /**
     * @var string
     *
     * @ORM\Column(name="width", type="string", length=255, nullable=true)
     */
    protected $width;

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
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

    public function __toString()
    {
        return $this->text;
    }
}

