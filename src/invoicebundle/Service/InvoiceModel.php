<?php

namespace Wecoders\InvoiceBundle\Service;

use AppBundle\Entity\InvoiceInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InvoiceModel
{
//    const INVOICE_TYPE_DEFAULT = 'invoice';
//    const INVOICE_TYPE_CORRECTION = 'correction';
//    const INVOICE_TYPE_PROFORMA = 'proforma';
    private $moneyCalculator;

    private $em;

    private $container;

    public function __construct(
        MoneyCalculator $moneyCalculator,
        EntityManager $em,
        ContainerInterface $container
    )
    {
        $this->moneyCalculator = $moneyCalculator;
        $this->em = $em;
        $this->container = $container;
    }

    public function fullInvoicePath($kernelRootDir, InvoicePathInterface $invoice, $relativeDirectoryPath)
    {
        $filename = $this->generateFilenameFromNumber($invoice->getNumber());
        return $this->getFullInvoiceTypePath($kernelRootDir, $invoice->getCreatedDate(), $relativeDirectoryPath) . '/' . $filename;
    }

    public function getFullInvoiceTypePath($kernelRootDir, \DateTime $createdDate, $relativeDirectoryPath)
    {
        $datePieces = explode('-', $createdDate->format('Y-m-d'));
        $invoicesPath = $kernelRootDir . '/../' . $relativeDirectoryPath;
        $fullPath = $invoicesPath . '/' . $datePieces[0] . '/' . $datePieces[1];

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        return $fullPath;
    }

    public function invoiceDirDataPiece($invoice)
    {
        $datePieces = explode('-', $invoice->getCreatedDate()->format('Y-m-d'));
        return $datePieces[0] . '/' . $datePieces[1];
    }

    public function dataToInvoiceProductGroupObjects($data)
    {
        $result = [];
        foreach ($data as $item) {
            $products = $this->dataToInvoiceProductObjects($item['services']);
            $rabates = $this->dataToInvoiceProductObjects($item['rabates']);

            $invoiceProductGroup = new InvoiceProductGroup();
            $invoiceProductGroup->setId($item['id']);
            $invoiceProductGroup->setTitle($item['telephone']);
            $invoiceProductGroup->setProducts($products);
            $invoiceProductGroup->setRabates($rabates);

            $result[] = $invoiceProductGroup;
        }

        if (count($result)) {
            return $result;
        }
        return null;
    }

    public function dataToInvoiceProductObjects($dataProducts)
    {
        $result = [];
        foreach ($dataProducts as $product) {
            $invoiceProduct = new InvoiceProduct(new Helper());
            $invoiceProduct->setId($product['id']);
            $invoiceProduct->setTitle($product['title']);
            $invoiceProduct->setVatPercentage($product['vatPercentage']);
            $invoiceProduct->setNetValue($product['netValue']);
            $invoiceProduct->setGrossValue(isset($product['grossValue']) ? $product['grossValue'] : null);
            $invoiceProduct->isCorrection = (isset($product['isCorrection']) ? $product['isCorrection'] : null);
            $invoiceProduct->productData = (isset($product['productData']) ? $product['productData'] : null);
            $invoiceProduct->afterCorrectionData = (isset($product['afterCorrectionData']) ? $product['afterCorrectionData'] : null);

            $result[] = $invoiceProduct;
        }

        if (count($result)) {
            return $result;
        }
        return null;
    }

    public function generateFilenameFromNumber($number)
    {
        return str_replace('/', '-', $number);
    }

    public function displayInvoice($dir, $filename)
    {
        $fullInvoicePath = $dir. '/' . $filename;
        $fullInvoicePathWithExtension = $fullInvoicePath . '.pdf';

        if (file_exists($fullInvoicePathWithExtension)) {
            $fullInvoicePath = $fullInvoicePathWithExtension;
        }

        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
        echo readfile($fullInvoicePath);
        die;
    }

    public function calculateSummaryValuesInInvoiceType(InvoiceInterface $objectToUpdate, EntityManager $em, $flush = true)
    {
        $summaryNetValue = 0;
        $summaryGrossValue = 0;
        $summaryVatValue = 0;

        $data = $objectToUpdate->getData();
        if ($data) {
            foreach ($data as $item) {
                $products = $item['services'];
                if ($products) {
                    foreach ($products as $product) {
                        $grossValue = 0;
                        $summaryNetValue += $this->moneyCalculator->netValue($product['netValue'], $grossValue, $product['vatPercentage']);
                        $summaryGrossValue += $this->moneyCalculator->grossValue($product['netValue'], $grossValue, $product['vatPercentage']);
                        $summaryVatValue += $this->moneyCalculator->vatValue($product['netValue'], $grossValue, $product['vatPercentage']);
                    }
                }

                $rabates = $item['rabates'];
                if ($rabates) {
                    foreach ($rabates as $rabate) {
                        $grossValue = 0;
                        $summaryNetValue -= $this->moneyCalculator->netValue($rabate['netValue'], $grossValue, $rabate['vatPercentage']);
                        $summaryGrossValue -= $this->moneyCalculator->grossValue($rabate['netValue'], $grossValue, $rabate['vatPercentage']);
                        $summaryVatValue -= $this->moneyCalculator->vatValue($rabate['netValue'], $grossValue, $rabate['vatPercentage']);
                    }
                }
            }

            $objectToUpdate->setSummaryNetValue($summaryNetValue);
            $objectToUpdate->setSummaryGrossValue($summaryGrossValue);
            $objectToUpdate->setSummaryVatValue($summaryVatValue);

            $em->persist($objectToUpdate);
            if ($flush) {
                $em->flush();
            }
        }
    }

    public function getInvoicesByClient($client, $entity)
    {
        if (!$client) {
            return null;
        }

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from($entity, 'a')
            ->where('a.client = :client')
            ->setParameters([
                'client' => $client
            ])
            ->orderBy('a.createdDate', 'DESC')
            ->getQuery()
        ;

        return $q->getResult();
    }

    public function calculateSummaryFromInvoicesProforma($invoices)
    {
        $result = 0;

        if ($invoices) {
            foreach ($invoices as $invoice) {
                $result += str_replace(',', '', $invoice->getSummaryGrossValue());
            }
        }

        return $result;
    }

    public function applyStateIsGeneratedFileExist(&$invoice, $dir)
    {
        $filename = $this->generateFilenameFromNumber($invoice->getNumber());
        if (file_exists($dir . '/' . $this->invoiceDirDataPiece($invoice) . '/' . $filename . '.pdf')) {
            $invoice->setIsGeneratedFileExist(true);
        } else {
            $invoice->setIsGeneratedFileExist(false);
        }
    }

    public function applyStateIsNotActual(&$document, $mostActiveSettlementDocument = null, $isSettlementDocument = false)
    {
        die('disabled functionality');

        $lastDayOfMonthOfSettlement = null;
        if ($mostActiveSettlementDocument && $mostActiveSettlementDocument->getBillingPeriodTo()) {
            $billingPeriodDayOfMostActiveSettlementDocument = $mostActiveSettlementDocument->getBillingPeriodTo()->format('d');
            if ($billingPeriodDayOfMostActiveSettlementDocument > 1) {
                /** @var \DateTime $lastDayOfMonthOfSettlement */
                $lastDayOfMonthOfSettlement = clone $mostActiveSettlementDocument->getBillingPeriodTo();
                $lastDayOfMonthOfSettlement->modify('first day of next month');
                $lastDayOfMonthOfSettlement->setTime(0, 0, 0);
            } else {
                $lastDayOfMonthOfSettlement = clone $mostActiveSettlementDocument->getBillingPeriodTo();
                $lastDayOfMonthOfSettlement->setTime(0, 0, 0);
            }
        }
        $documentBillingPeriodIsBeforeSettlement = !$isSettlementDocument && $mostActiveSettlementDocument && $document->getBillingPeriodTo() && $lastDayOfMonthOfSettlement && $lastDayOfMonthOfSettlement > $document->getBillingPeriodTo();
        if ($documentBillingPeriodIsBeforeSettlement) {
            $document->setIsNotActual(true);
            return;
        }

        if (method_exists($document, 'getCorrections') && count($document->getCorrections())) {
            // if document have corrections then is not actual
            $document->setIsNotActual(true);
        } elseif (method_exists($document, 'getInvoice') && $document->getInvoice()) {
            // if document is correction (have parent document) check if this is last document of this type for this parent
            $parent = $document->getInvoice();
            $mostActiveCorrection = $this->getMostActiveCorrectionFromDocument($parent);
            if ($document->getId() != $mostActiveCorrection->getId()) {
                $document->setIsNotActual(true);
            } else {
                $document->setIsNotActual(false);
            }
        } else {
            $document->setIsNotActual(false);
        }
    }

    public function getMostActiveCorrectionFromDocument($document)
    {
        die('disabled functionality');

        $corrections = $document->getCorrections();
        $mostActive = null;

        if ($corrections) {
            foreach ($corrections as $correction) {
                if (!$mostActive) {
                    $mostActive = $correction;
                }

                if ($correction->getCreatedAt() > $mostActive->getCreatedAt()) {
                    $mostActive = $correction;
                }
            }
        }

        return $mostActive;
    }

    /**
     * @param $document
     * @param $directoryRelative
     */
    public function deleteFile($document, $directoryRelative)
    {
        $invoicePath = $this->fullInvoicePath($this->container->get('kernel')->getRootDir(), $document, $directoryRelative);
        if (file_exists($invoicePath . '.pdf')) {
            unlink($invoicePath . '.pdf');
        }
        if (file_exists($invoicePath . '.docx')) {
            unlink($invoicePath . '.docx');
        }
    }
}