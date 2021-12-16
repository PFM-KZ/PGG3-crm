<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\ClientModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;

class UpdateInvoicesPeselCommand extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;
    private $clientModel;

    public function __construct(ContainerInterface $container, EntityManager $em, ClientModel $clientModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->clientModel = $clientModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:update-invoices-pesel')
            ->setDescription('Update invoices pesel.');
    }

    protected function getDataRows($file, $firstDataRowIndex, $highestColumn)
    {
//        $reader = new Xlsx();
        $reader = new Csv();
        $reader->setInputEncoding('CP1250');
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn++;

        $rows = [];

        for ($row = $firstDataRowIndex; $row <= $highestRow; ++$row) {
            $rows[$row] = [];
            for ($col = 'A'; $col != $highestColumn; ++$col) {
                $rows[$row][] = $worksheet->getCell($col . $row)->getFormattedValue();
            }
        }

        return $rows;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container = $this->container;
        $clientModel = $this->clientModel;

        $kernelRootDir = $container->get('kernel')->getRootDir();
        $rows = $this->getDataRows($kernelRootDir . '/../var/data/enrexenergy.csv', 2, 'AO');

        $clients = [];
        foreach ($rows as $key => $row) {
            $badgeId = $row[10];
            $invoiceNumber = $row[17];

            if (!array_key_exists($badgeId, $clients)) { // badgeId
                $clients[$badgeId] = [
                    'data' => [
                        'badgeId' => $clientModel->prependZerosToMatchLength($row[10], ClientModel::BADGE_ID_LENGTH),
                    ],
                    'invoices' => [],
                ];
            }

            if (!array_key_exists($invoiceNumber, $clients[$badgeId]['invoices'])) { // invoice number
                $clients[$badgeId]['invoices'][$invoiceNumber] = [];
            }
            $clients[$badgeId]['invoices'][$invoiceNumber][] = $row;
        }


        $counter = 1;
        foreach ($clients as $clientData) {

            foreach ($clientData['invoices'] as $key => $invoicesData) {

                $invoiceDb = null;
                foreach ($invoicesData as $index => $invoiceData) {
                    $pesel = $invoiceData[2];
                    $invoiceNumber = $invoiceData[17];

                    if ($index === 0) {
                        /** @var InvoiceProforma $invoiceDb */
                        $invoiceDb = $em->getRepository('WecodersEnergyBundle:InvoiceProforma')->findOneBy(['number' => $invoiceNumber]);
                        $invoiceDb->setClientPesel($pesel);
                    }

                    // last position
                    if ($index == count($invoicesData) - 1) {
                        $em->merge($invoiceDb);
                        $em->flush();
                    }
                }
            }


            dump($counter);
            $counter++;
            $em->clear();
        }

        dump('Success');
    }

}