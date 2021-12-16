<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;

/**
 * Invoice
 *
 * @ORM\Table(name="invoice_energy")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Invoice extends InvoiceBase implements InvoiceInterface, IsDocumentReadyForBankAccountChangeInterface
{
    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceCorrection", mappedBy="invoice", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $corrections;

    /**
     * @return mixed
     */
    public function getCorrections()
    {
        return $this->corrections;
    }

    /**
     * @param mixed $corrections
     */
    public function setCorrections($corrections)
    {
        $this->corrections = $corrections;
    }

    public function getCorrectionNumbers()
    {
        $numbers = [];

        if ($this->corrections) {
            foreach ($this->corrections as $correction) {
                $numbers[] = $correction;
            }
        }

        if (count($numbers)) {
            return implode(', ', $numbers);
        }

        return null;
    }

    /**
     * @var array
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    protected $content;

    /**
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param array $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}

