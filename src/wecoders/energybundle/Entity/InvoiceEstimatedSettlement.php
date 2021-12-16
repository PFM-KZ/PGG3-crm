<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;

/**
 * InvoiceEstimatedSettlement
 *
 * @ORM\Table(name="invoice_estimated_settlement")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class InvoiceEstimatedSettlement extends InvoiceBase implements InvoiceInterface, ICollectiveMarkable, IsDocumentReadyForBankAccountChangeInterface, HasIncludedDocumentsInterface
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

    /**
     * @param array $data
     *
     * @return InvoiceEstimatedSettlement
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
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlementCorrection", mappedBy="invoice", cascade={"persist","remove"}, orphanRemoval=true)
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
     * @var boolean
     *
     * @ORM\Column(name="is_first", type="boolean", nullable=true)
     */
    protected $isFirst = false;

    /**
     * @return bool
     */
    public function getIsFirst()
    {
        return $this->isFirst;
    }

    /**
     * @param bool $isFirst
     */
    public function setIsFirst($isFirst)
    {
        $this->isFirst = $isFirst;
    }

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_last", type="boolean", nullable=true)
     */
    protected $isLast = false;

    /**
     * @return bool
     */
    public function getIsLast()
    {
        return $this->isLast;
    }

    /**
     * @param bool $isLast
     */
    public function setIsLast($isLast)
    {
        $this->isLast = $isLast;
    }

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_in_invoice_collective", type="boolean", nullable=true)
     */
    protected $isInInvoiceCollective = false;

    /**
     * @return bool
     */
    public function getIsInInvoiceCollective()
    {
        return $this->isInInvoiceCollective;
    }

    /**
     * @param bool $isInInvoiceCollective
     */
    public function setIsInInvoiceCollective($isInInvoiceCollective)
    {
        $this->isInInvoiceCollective = $isInInvoiceCollective;
    }

    /**
     * @var boolean
     *
     * @ORM\Column(name="invoice_collective_number", type="string", length=255, nullable=true)
     */
    protected $invoiceCollectiveNumber;

    /**
     * @return bool
     */
    public function getInvoiceCollectiveNumber()
    {
        return $this->invoiceCollectiveNumber;
    }

    /**
     * @param bool $invoiceCollectiveNumber
     */
    public function setInvoiceCollectiveNumber($invoiceCollectiveNumber)
    {
        $this->invoiceCollectiveNumber = $invoiceCollectiveNumber;
    }

}

