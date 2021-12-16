<?php

namespace Wecoders\EnergyBundle\Twig;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use Twig_Extension;
use Wecoders\EnergyBundle\Entity\HasIncludedDocumentsInterface;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;

class IncludedDocumentExtension extends Twig_Extension
{
    /** @var EntityManager */
    private $em;
    private $initializer;

    public function __construct(EntityManager $em, Initializer $initializer)
    {
        $this->em = $em;
        $this->initializer = $initializer;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('includedDocument', array($this, 'includedDocument')),
        );
    }

    public function includedDocument(InvoiceInterface $document, $structure)
    {
        $settlements = array_merge($structure['invoiceSettlement']['records'], $structure['invoiceEstimatedSettlement']['records']);
        if (!$settlements) {
            return null;
        }
        /** @var HasIncludedDocumentsInterface $settlement */
        foreach ($settlements as $settlement) {
            $includedDocuments = $settlement->getIncludedDocuments();
            if (!$includedDocuments) {
                return null;
            }

            /** @var SettlementIncludedDocument $includedDocument */
            foreach ($includedDocuments as $includedDocument) {
                if ($includedDocument->getDocumentNumber() == $document->getNumber()) {
                    return $settlement->getNumber();
                }
            }
        }

        return null;
    }
}