<?php

namespace Wecoders\EnergyBundle\Entity\DocumentTable;

use Doctrine\ORM\Mapping as ORM;

/**
 * DocumentTable
 *
 * @ORM\Table(name="document_table")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\DocumentTableRepository")
 * @ORM\HasLifecycleCallbacks
 */
class DocumentTable
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
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255)
     */
    protected $token;

    /**
     * @var string
     *
     * @ORM\Column(name="width", type="string", length=255)
     */
    protected $width;

    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\DocumentTable\TableHeading", mappedBy="documentTable", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $tableHeadings;

    public function addTableHeading(TableHeading $tableHeading)
    {
        $this->tableHeadings[] = $tableHeading;
        $tableHeading->setDocumentTable($this);

        return $this;
    }

    public function removeTableHeading(TableHeading $tableHeading)
    {
        $this->tableHeadings->removeElement($tableHeading);
    }

    public function getTableHeadings()
    {
        return $this->tableHeadings;
    }

    public function setTableHeadings($tableHeadings)
    {
        $this->tableHeadings = $tableHeadings;
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
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
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

    public function __toString()
    {
        return $this->title;
    }
}

