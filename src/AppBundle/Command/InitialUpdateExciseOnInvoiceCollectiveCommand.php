<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\InvoiceCollective;

class InitialUpdateExciseOnInvoiceCollectiveCommand extends Command
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
        $this->setName('appbundle:initial-update-excise-on-invoice-collective')
            ->setDescription('Initial update excise on invoice collective');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // collective
        $records = $this->em->getRepository('WecodersEnergyBundle:InvoiceCollective')->findAll();

        // validate
        foreach ($records as $record) {
            $data = $record->getData();
            foreach ($data as &$item) {
                $this->fetchInvoice($item['number']);
            }
        }
        dump('Validated - OK');


        /** @var InvoiceCollective $record */
        $index = 1;
        foreach ($records as $record) {
            $data = $record->getData();
            $recordExciseValue = 0;
            foreach ($data as &$item) {
                $invoice = $this->fetchInvoice($item['number']);
                $exciseValue = $invoice->getExciseValue();
                $item['exciseValue'] = $exciseValue;
                $recordExciseValue += $exciseValue;
            }

            $record->setData($data);
            $record->setExciseValue($recordExciseValue);

            $this->em->persist($record);
            $this->em->flush($record);

            dump($index);
            $index++;
        }

        dump('Success');
    }

    private function fetchInvoice($number)
    {
        $invoice = $this->em->getRepository('WecodersEnergyBundle:InvoiceSettlement')->findOneBy(['number' => $number]);
        if (!$invoice) {
            $invoice = $this->em->getRepository('WecodersEnergyBundle:InvoiceEstimatedSettlement')->findOneBy(['number' => $number]);
            if (!$invoice) {
                dump('Cannot find invoice based on invoice number: ' . $number);
                die;
            }
        }

        return $invoice;
    }

}