<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\HasIncludedDocumentsInterface;
use Wecoders\EnergyBundle\Entity\InvoiceBase;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;

class InitialUpdateInvoiceProformaExciseValueOnSettlementsCommand extends Command
{
    /* @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('gcrmcrmbundle:initial-update-invoice-proforma-excise-value-on-settlements')
            ->setDescription('Update tables.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        /** @var Client $client */
        $entities = [
            'WecodersEnergyBundle:InvoiceSettlement',
            'WecodersEnergyBundle:InvoiceSettlementCorrection',
            'WecodersEnergyBundle:InvoiceEstimatedSettlement',
            'WecodersEnergyBundle:InvoiceEstimatedSettlementCorrection',
        ];

        foreach ($entities as $entity) {
            $count = 1;
            dump($entity);
            $invoices = $this->em->getRepository($entity)->findAll();
            /** @var HasIncludedDocumentsInterface $invoice */
            foreach ($invoices as $invoice) {
                $includedDocuments = $invoice->getIncludedDocuments();
                if (!$includedDocuments) {
                    continue;
                }

                /** @var SettlementIncludedDocument $includedDocument */
                foreach ($includedDocuments as &$includedDocument) {
                    /** @var InvoiceBase $includedFetchedDocument */
                    $includedFetchedDocument = $this->em->getRepository('WecodersEnergyBundle:InvoiceProforma')->findOneBy([
                        'number' => $includedDocument->getDocumentNumber()
                    ]);
                    if (!$includedFetchedDocument) {
                        $includedFetchedDocument = $this->em->getRepository('WecodersEnergyBundle:InvoiceProformaCorrection')->findOneBy([
                            'number' => $includedDocument->getDocumentNumber()
                        ]);
                    }

                    if (!$includedFetchedDocument) {
                        die('error');
                    }

                    $includedDocument->setExciseValue($includedFetchedDocument->getExciseValue());
                }

                $invoice->setIncludedDocuments($includedDocuments);
                $this->em->persist($invoice);
                $this->em->flush($invoice);

                dump($count);
                $count++;
            }
        }

        dump('Success');
    }

}