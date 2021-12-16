<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;

/**
 * InvoiceSettlementCorrection
 *
 * @ORM\Table(name="invoice_settlement_correction_energy")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class InvoiceSettlementCorrection extends InvoiceBase implements InvoiceInterface, IsDocumentReadyForBankAccountChangeInterface
{
    /**
     * @var array
     *
     * @ORM\Column(name="included_documents", type="text", nullable=true)
     */
    protected $includedDocuments;

    // used only for calculations, to inject already prepared data
    public function setIncludedDocumentsSerializedData($data)
    {
        $this->includedDocuments = $data;
    }

    /**
     * @return array
     */
    public function getIncludedDocuments()
    {
        if (!$this->includedDocuments) {
            return null;
        }

        $unserialized = json_decode($this->includedDocuments, true);

        $settlementIncludedDocuments = [];
        foreach ($unserialized as &$itemData) {
            $settlementIncludedDocument = new SettlementIncludedDocument();
            $settlementIncludedDocument = $settlementIncludedDocument->create(
                $itemData['documentNumber'],
                $itemData['netValue'],
                $itemData['vatValue'],
                $itemData['grossValue'],
                array_key_exists('exciseValue', $itemData) ? $itemData['exciseValue'] : null,
                \DateTime::createFromFormat('Y-m-d', $itemData['billingPeriodFrom']),
                \DateTime::createFromFormat('Y-m-d', $itemData['billingPeriodTo'])
            );

            $settlementIncludedDocuments[] = $settlementIncludedDocument;
        }

        return $settlementIncludedDocuments;
    }

    public function getIncludedDocumentsNumbersValue()
    {
        $documents = $this->getIncludedDocuments();
        $numbers = [];
        if ($documents) {
            /** @var SettlementIncludedDocument $document */
            foreach ($documents as $document) {
                $numbers[] = $document->getDocumentNumber();
            }
        }

        return implode(',', $numbers);
    }

    public function getIncludedDocumentsNetValue()
    {
        return $this->getIncludedDocumentsValueByMethod('getNetValue');
    }

    public function getIncludedDocumentsVatValue()
    {
        return $this->getIncludedDocumentsValueByMethod('getVatValue');
    }

    public function getIncludedDocumentsGrossValue()
    {
        return $this->getIncludedDocumentsValueByMethod('getGrossValue');
    }

    public function getIncludedDocumentsExciseValue()
    {
        return $this->getIncludedDocumentsValueByMethod('getExciseValue');
    }

    private function getIncludedDocumentsValueByMethod($method)
    {
        $documents = $this->getIncludedDocuments();
        $value = 0;
        if ($documents) {
            /** @var SettlementIncludedDocument $document */
            foreach ($documents as $document) {
                $value += $document->$method();
            }
        }

        return $value;
    }



    /**
     * @param array $data
     *
     * @return InvoiceSettlementCorrection
     */
    public function setIncludedDocuments($data)
    {
        $tmp = [];
        /** @var SettlementIncludedDocument $item */
        foreach ($data as &$item) {
            $itemData = $item->hydrateToArray();

            // format dates
            foreach ($itemData as &$property) {
                if ($property instanceof \DateTime) {
                    $property = $property->format('Y-m-d');
                }
            }

            $tmp[] = $itemData;
        }

        $serialized = json_encode($tmp);
        $this->includedDocuments = $serialized;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceSettlement", inversedBy="corrections")
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

