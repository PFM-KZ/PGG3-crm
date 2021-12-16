<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\InvoiceCollective;

class InitialUpdateTariffSingleToDistributionAndSellerOnDocumentsCommand extends Command
{
    private $em;
    private $contractModel;

    public function __construct(EntityManager $em, ContractModel $contractModel)
    {
        $this->em = $em;
        $this->contractModel = $contractModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:initial-update-tariff-single-to-distribution-and-seller-on-documents')
            ->setDescription('Initial update tariffs single to multiple - rewrite on documents');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $entities = [
            'WecodersEnergyBundle:InvoiceProforma',
            'WecodersEnergyBundle:InvoiceProformaCorrection',
            'WecodersEnergyBundle:InvoiceSettlement',
            'WecodersEnergyBundle:InvoiceSettlementCorrection',
            'WecodersEnergyBundle:InvoiceEstimatedSettlement',
            'WecodersEnergyBundle:InvoiceEstimatedSettlementCorrection',
        ];

        // Invoice Corrections
        foreach ($entities as $entity) {
            $records = $this->em->getRepository($entity)->findAll();

            $index = 1;
            foreach ($records as $record) {
                if ($record->getSellerTariff()) {
                    continue;
                }

                $record->setDistributionTariff($record->getTariff());
                $record->setSellerTariff($record->getTariff());

                $this->em->persist($record);
                $this->em->flush($record);

                dump($index);
                $index++;
            }
        }


        // collective
        $records = $this->em->getRepository('WecodersEnergyBundle:InvoiceCollective')->findAll();

        /** @var InvoiceCollective $record */
        $index = 1;
        foreach ($records as $record) {
            $invoicesData = $record->getInvoicesData();
            foreach ($invoicesData as &$invoiceData) {
                if (isset($invoiceData['sellerTariff']) && $invoiceData['sellerTariff']) {
                    continue;
                }

                $invoiceData['distributionTariff'] = $invoiceData['tariff'];
                $invoiceData['sellerTariff'] = $invoiceData['tariff'];
            }
            $record->setInvoicesData($invoicesData);

            $this->em->persist($record);
            $this->em->flush($record);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}