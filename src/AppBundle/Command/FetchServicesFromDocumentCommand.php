<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\ContractModel;
use GCRM\CRMBundle\Service\GTU;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\GTUInterface;

class FetchServicesFromDocumentCommand extends Command
{
    private $em;
    private $gtuModel;

    public function __construct(EntityManager $em, GTU $gtuModel)
    {
        $this->em = $em;
        $this->gtuModel = $gtuModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:fetch-services-from-document-command')
//            ->addArgument('entityName', InputArgument::REQUIRED, 'Give full entity name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

//        $invoices = $this->em->getRepository($input->getArgument('entityName'))->findAll();
        $invoicesByTypes = [];
        $invoicesByTypes[] = $this->em->getRepository('WecodersEnergyBundle:Invoice')->findAll();
        $invoicesByTypes[] = $this->em->getRepository('WecodersEnergyBundle:InvoiceCorrection')->findAll();
        $invoicesByTypes[] = $this->em->getRepository('WecodersEnergyBundle:InvoiceProforma')->findAll();
        $invoicesByTypes[] = $this->em->getRepository('WecodersEnergyBundle:InvoiceProformaCorrection')->findAll();
        $invoicesByTypes[] = $this->em->getRepository('WecodersEnergyBundle:InvoiceSettlement')->findAll();
        $invoicesByTypes[] = $this->em->getRepository('WecodersEnergyBundle:InvoiceSettlementCorrection')->findAll();
        $invoicesByTypes[] = $this->em->getRepository('WecodersEnergyBundle:InvoiceEstimatedSettlement')->findAll();
        $invoicesByTypes[] = $this->em->getRepository('WecodersEnergyBundle:InvoiceEstimatedSettlementCorrection')->findAll();

        /** @var GTUInterface $invoice */
        $index = 1;
        $services = [];
        foreach ($invoicesByTypes as $invoicesByType) {
            foreach ($invoicesByType as $invoice) {
                $data = $invoice->getData();
                if (!$data) {
                    dump('not found data');
                    dump($index);
                    $index++;
                    continue;
                }

                foreach ($data as $item) {
                    if (!isset($item['services'])) {
                        dump('not found services');
                        dump($index);
                        $index++;
                        continue;
                    }

                    foreach ($item['services'] as $service) {
                        if (!in_array($service['title'], $services)) {
                            $services[] = $service['title'];
                        }
                    }
                }

                dump($index);
                $index++;
            }
        }

        dump($services);

        dump('Success');
    }

}