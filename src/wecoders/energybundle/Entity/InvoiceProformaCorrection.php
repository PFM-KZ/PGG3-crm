<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InvoiceProformaCorrection
 *
 * @ORM\Table(name="invoice_proforma_correction_energy")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class InvoiceProformaCorrection extends InvoiceBase implements InvoiceInterface, IsDocumentReadyForBankAccountChangeInterface
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceProforma", inversedBy="corrections")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id")
     */
    private $invoice;

    /**
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param mixed $invoice
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
    }
}

