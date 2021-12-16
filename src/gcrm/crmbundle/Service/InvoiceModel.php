<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Invoice;
use GCRM\CRMBundle\Entity\InvoiceProforma;
use GCRM\CRMBundle\Entity\InvoiceInterface;
use GCRM\CRMBundle\Entity\InvoiceCorrection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InvoiceModel
{
    const INVOICE_CORRECTION_TYPE = 'correction';
    const INVOICE_TYPE = 'invoice';
    const INVOICE_PROFORMA = 'invoice_proforma';

    /** @var  EntityManager */
    private $em;

    /** @var  FileActionsModel */
    private $fileActionsModel;

    private $paymentModel;

    private $container;

    private $templating;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
        $this->templating = $container->get('templating');
    }

    public function __construct(EntityManager $em, FileActionsModel $fileActionsModel, PaymentModel $paymentModel)
    {
        $this->em = $em;
        $this->fileActionsModel = $fileActionsModel;
        $this->paymentModel = $paymentModel;
    }

    public function getNextInvoiceCorrectionNr()
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a.correctionNr'])
            ->from('GCRMCRMBundle:Invoices', 'a')
            ->orderBy('a.correctionNr', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
        ;

        $lastInvoiceAdded = $q->getResult();
        if (!$lastInvoiceAdded || (isset($lastInvoiceAdded[0]) && !$lastInvoiceAdded[0]['correctionNr'])) {
            return 1;
        }

        $nr = explode('/', $lastInvoiceAdded[0]['correctionNr'])[0];
        return ++$nr;
    }

    public function getInvoicesFromDirectory($dir)
    {
        $invoices = [];
        $this->fileActionsModel->generateFilesStructure($dir, $invoices);

        $result = [];
        foreach ($invoices as $invoice) {
            $result[] = $invoice;
        }

        if (isset($result[0])) {
            return $result[0];
        }
        return [];
    }

    public function getClientByInvoiceNumber($invoiceNumber, $structure, $tableToCheck, $idFieldToCheck, $tokenToCheck = '#id#')
    {
        $tokenIndex = null;
        $pieces = explode('/', $structure);
        for ($i = 0; $i < count($pieces); $i++) {
            if ($pieces[$i] == $tokenToCheck) {
                $tokenIndex = $i;
            }
        }

        $pieces = explode('/', $invoiceNumber);

        $client = $this->em->getRepository($tableToCheck)->findOneBy([
            $idFieldToCheck => $pieces[$tokenIndex]
        ]);

        return $client;
    }

    public function calculateSummaryFromInvoicesProforma($invoices)
    {
        $result = 0;

        if ($invoices) {
            /** @var InvoiceProforma $invoice */
            foreach ($invoices as $invoice) {
                $result += str_replace(',', '', $invoice->getSummaryGrossValue());
            }
        }

        return $result;
    }

    public function calculateSummaryFromInvoices($invoices)
    {
        $result = 0;

        if ($invoices) {
            /** @var Invoice $invoice */
            foreach ($invoices as $invoice) {
                /** @var InvoiceCorrection $mostActiveCorrection */
                $mostActiveCorrection = $this->getMostActiveCorrectionFromInvoice($invoice);

                if ($mostActiveCorrection) {
                    $result += str_replace(',', '', $mostActiveCorrection->getSummaryGrossValue());
                } else {
                    $result += str_replace(',', '', $invoice->getSummaryGrossValue());
                }
            }
        }

        return $result;
    }

    private function getMostActiveCorrectionFromInvoice(Invoice $invoice)
    {
        $corrections = $invoice->getCorrections();
        $mostActive = null;

        if ($corrections) {
            /** @var InvoiceCorrection $correction */
            foreach ($corrections as $correction) {
                if (!$mostActive) {
                    $mostActive = $correction;
                }

                if ($correction->getCreatedDate() > $mostActive->getCreatedDate()) {
                    $mostActive = $correction;
                }
            }
        }

        return $mostActive;
    }

    public function getInvoicesProformaByClient($client)
    {
        /** @var Client $client */
        if (!$client) {
            return null;
        }

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('WecodersEnergyBundle:InvoiceProforma', 'a')
            ->where('a.client = :client')
            ->setParameters([
                'client' => $client
            ])
            ->orderBy('a.billingPeriod', 'DESC')
            ->getQuery()
        ;

        return $q->getResult();
    }

    public function getInvoicesByClient($client)
    {
        /** @var Client $client */
        if (!$client) {
            return null;
        }

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('GCRMCRMBundle:Invoice', 'a')
            ->where('a.client = :client')
            ->setParameters([
                'client' => $client
            ])
            ->orderBy('a.createdDate', 'DESC')
            ->getQuery()
        ;

        return $q->getResult();
    }

    public function getInvoicesByBadgeId($badgeId)
    {
        if (!$badgeId) {
            return null;
        }

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('GCRMCRMBundle:Invoice', 'a')
            ->where('a.number LIKE :badgeId')
            ->setParameters([
                'badgeId' => $badgeId . '%'
            ])
            ->orderBy('a.createdDate', 'DESC')
            ->getQuery()
        ;

        return $q->getResult();
    }

    public function applyIsPaidState($invoices, Balance $balance, $updateDb = false)
    {
        $balancePaid = $balance->getPaid();

        if ($invoices) {
            // signal to set to false every invoice from this state where activated,
            $balanceOverloaded = false;

            /** @var Invoice $invoice */
            foreach ($invoices as $invoice) {
                /** @var InvoiceCorrection $mostActiveCorrection */
                $mostActiveCorrection = $this->getMostActiveCorrectionFromInvoice($invoice);

                if ($mostActiveCorrection) {
                    if ($balanceOverloaded || (string) $balancePaid < (string) $mostActiveCorrection->getSummaryGrossValue()) {
                        $mostActiveCorrection->setIsPaid(false);
                        $balanceOverloaded = true;
                    } else {
                        $mostActiveCorrection->setIsPaid(true);
                        $balancePaid -= $mostActiveCorrection->getSummaryGrossValue();
                    }

                    if ($updateDb) {
                        $this->em->persist($mostActiveCorrection);
                        $this->em->flush();
                    }
                } else {
                    if ($balanceOverloaded || (string) $balancePaid < (string) $invoice->getSummaryGrossValue()) {
                        $invoice->setIsPaid(false);
                        $balanceOverloaded = true;
                    } else {
                        $invoice->setIsPaid(true);
                        $balancePaid -= $invoice->getSummaryGrossValue();
                    }

                    if ($updateDb) {
                        $this->em->persist($invoice);
                        $this->em->flush();
                    }
                }
            }
        }

        return $invoices;
    }

    public function applyIsPaidStateProforma($invoices, Balance $balance, $updateDb = false)
    {
        $balancePaid = $balance->getPaid();

        if ($invoices) {
            // signal to set to false every invoice from this state where activated,
            $balanceOverloaded = false;

            /** @var InvoiceProforma $invoice */
            foreach ($invoices as $invoice) {
                if ($balanceOverloaded || (string) $balancePaid < (string) $invoice->getSummaryGrossValue()) {
                    $invoice->setIsPaid(false);
                    $balanceOverloaded = true;
                } else {
                    $invoice->setIsPaid(true);
                    $balancePaid -= $invoice->getSummaryGrossValue();
                }

                if ($updateDb) {
                    $this->em->persist($invoice);
                    $this->em->flush();
                }
            }
        }

        return $invoices;
    }

    public function updateInvoicesProformaIsPaidStateByClient(Client $client, $updateDb = false, $clearCache = false)
    {
        if ($clearCache) {
            $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        }

        $invoices = $this->getInvoicesProformaByClient($client);

        // Payments
        $payments = $this->paymentModel->getPaymentsByNumber($client->getAccountNumberIdentifier()->getNumber());


        // Balance
        $balance = new Balance();
        $balance->setInitialBalance($client->getInitialBalance());
        $balance->setToPay($this->calculateSummaryFromInvoicesProforma($invoices));
        $balance->setPaid($this->paymentModel->calculateSummaryFromPayments($payments));


        if ($invoices) {
            $invoices = $this->applyIsPaidStateProforma(array_reverse($invoices), $balance, $updateDb);
            $invoices = array_reverse($invoices);
        }

        if ($clearCache) {
            $this->em->clear();
        }

        return $invoices;
    }

    public function updateInvoicesIsPaidStateByClient(Client $client, $updateDb = false, $clearCache = false)
    {
        if ($clearCache) {
            $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        }

        $invoices = $this->getInvoicesByBadgeId($client->getAccountNumberIdentifier()->getNumber());

        // Payments
        $payments = $this->paymentModel->getPaymentsByNumber($client->getAccountNumberIdentifier()->getNumber());


        // Balance
        $balance = new Balance();
        $balance->setInitialBalance($client->getInitialBalance());
        $balance->setToPay($this->calculateSummaryFromInvoices($invoices));
        $balance->setPaid($this->paymentModel->calculateSummaryFromPayments($payments));


        if ($invoices) {
            $invoices = $this->applyIsPaidState(array_reverse($invoices), $balance, $updateDb);
            $invoices = array_reverse($invoices);
        }

        if ($clearCache) {
            $this->em->clear();
        }

        return $invoices;
    }

    public function getFullInvoiceNewVersionPath(InvoiceInterface $invoice, $relativeDirectoryPath)
    {
        /** @var \DateTime $invoiceDate */
        $invoiceDate = $invoice->getCreatedDate();
        $datePieces = explode('-', $invoiceDate->format('Y-m-d'));

        $invoicesPath = $this->container->get('kernel')->getRootDir() . '/../' . $relativeDirectoryPath;

        $fullPath = $invoicesPath . '/' . $datePieces[0] . '/' . $datePieces[1];

        if (!file_exists($fullPath)) {
            if (!file_exists($invoicesPath)) {
                mkdir($invoicesPath);
            }

            if (!file_exists($invoicesPath . '/' . $datePieces[0])) {
                mkdir($invoicesPath . '/' . $datePieces[0]);
            }

            if (!file_exists($invoicesPath . '/' . $datePieces[0] . '/' . $datePieces[1])) {
                mkdir($invoicesPath . '/' . $datePieces[0] . '/' . $datePieces[1]);
            }
        }

        return $fullPath;
    }
}