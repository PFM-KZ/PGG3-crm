<?php

namespace Wecoders\InvoiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InvoiceNumberSettings
 *
 * @ORM\Table(name="invoice_number_settings")
 * @ORM\Entity(repositoryClass="Wecoders\InvoiceBundle\Repository\InvoiceNumberSettingsRepository")
 */
class InvoiceNumberSettings
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
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="structure", type="string", length=255)
     */
    private $structure;

    /**
     * @var boolean
     *
     * @ORM\Column(name="leading_zeros", type="boolean")
     */
    private $leadingZeros;

    /**
     * @var boolean
     *
     * @ORM\Column(name="exclude_ai_from_leading_zeros", type="boolean")
     */
    private $excludeAiFromLeadingZeros;

    /**
     * @var string
     *
     * @ORM\Column(name="reset_ai_at_new_month", type="boolean")
     */
    private $resetAiAtNewMonth;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     */
    private $code;

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

    /**
     * @return string
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * @param string $structure
     */
    public function setStructure($structure)
    {
        $this->structure = $structure;
    }

    /**
     * @return boolean
     */
    public function getLeadingZeros()
    {
        return $this->leadingZeros;
    }

    /**
     * @param boolean $leadingZeros
     */
    public function setLeadingZeros($leadingZeros)
    {
        $this->leadingZeros = $leadingZeros;
    }

    /**
     * @return boolean
     */
    public function getExcludeAiFromLeadingZeros()
    {
        return $this->excludeAiFromLeadingZeros;
    }

    /**
     * @param boolean $excludeAiFromLeadingZeros
     */
    public function setExcludeAiFromLeadingZeros($excludeAiFromLeadingZeros)
    {
        $this->excludeAiFromLeadingZeros = $excludeAiFromLeadingZeros;
    }

    /**
     * @return string
     */
    public function getResetAiAtNewMonth()
    {
        return $this->resetAiAtNewMonth;
    }

    /**
     * @param string $resetAiAtNewMonth
     */
    public function setResetAiAtNewMonth($resetAiAtNewMonth)
    {
        $this->resetAiAtNewMonth = $resetAiAtNewMonth;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    public function __toString()
    {
        return $this->title . ' - ' . $this->structure;
    }
}

