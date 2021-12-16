<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InvoiceProforma
 *
 * @ORM\Table(name="invoice_proforma_energy")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class InvoiceProforma extends InvoiceBase implements InvoiceInterface, IsDocumentReadyForBankAccountChangeInterface
{
    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection", mappedBy="invoice", cascade={"persist","remove"}, orphanRemoval=true)
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
}

