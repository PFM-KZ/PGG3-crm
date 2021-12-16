<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\ZipModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;
use Wecoders\InvoiceBundle\Service\InvoiceModel;

class EnveloWriteDataCommand extends Command
{
    private $em;

    private $container;
    private $invoiceModel;
    private $zipModel;

    public function __construct(ContainerInterface $container, EntityManager $em, InvoiceModel $invoiceModel, ZipModel $zipModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->invoiceModel = $invoiceModel;
        $this->zipModel = $zipModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:envelo-write-data')
            ->setDescription('Envelo.');
    }

    protected function getDataRows($file, $firstDataRowIndex, $highestColumn)
    {
        $reader = new Xls();
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

    protected function getSpreadsheet($file)
    {
        $reader = new Xls();
        return $spreadsheet = $reader->load($file);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container = $this->container;

        $kernelRootDir = $container->get('kernel')->getRootDir();
        $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);

        $invoices = $this->getInvoicesFrom(2019, 7, 1);
        $clients = $this->mergeInvoicesWithClients($invoices);

        // Output dir
        $now = new \DateTime();
        $currentYear = $now->format('Y');
        $currentMonth = $now->format('m');

        $enveloOutputDir = $kernelRootDir . '/../var/data/envelo/' . $currentYear . '/' . $currentMonth;
        if (!file_exists($enveloOutputDir)) {
            mkdir($enveloOutputDir, 0777, true);
        }

        $invoicesOutputDir = $enveloOutputDir . '/invoices';
        if (!file_exists($invoicesOutputDir)) {
            mkdir($invoicesOutputDir, 0777, true);
        }

        $zipOutputDir = $enveloOutputDir . '/zip';
        if (!file_exists($zipOutputDir)) {
            mkdir($zipOutputDir, 0777, true);
        }

        $dataOutputDir = $enveloOutputDir . '/data';
        if (!file_exists($dataOutputDir)) {
            mkdir($dataOutputDir, 0777, true);
        }



        $spreadsheetStartIndexFrom = 3;
        $outputFilesPaths = [];
        $enveloCounter = 1;
        $enveloPackageNumber = 1;
        $index = 0;
        $lastIterationIndex =  count($clients) - 1;
        foreach ($clients as $itemData) {
            $invoiceNumbers = $this->fetchInvoiceAiNumbersFromInvoices($itemData['invoices']);

            $outputFilePath = $invoicesOutputDir. '/' . $invoiceNumbers . '.pdf';
            $outputFilesPaths[] = $outputFilePath;
            $invoicesFromClientAbsolutePaths = $this->getInvoicesAbsoultePaths($kernelRootDir, $itemData['invoices']);
            $this->mergePdfFiles($invoicesFromClientAbsolutePaths, $outputFilePath);

            if ($enveloCounter == 41 || $index == $lastIterationIndex) { // Save and reset data
                $enveloFilename = 'envelo_' . $enveloPackageNumber . '.xls';
                $enveloAbsolutePath = $dataOutputDir . '/' . $enveloFilename;
                $this->saveSpreadsheet($spreadsheet, $enveloAbsolutePath);
                $outputFilesPaths[] = $enveloAbsolutePath;

                $zipFilename = 'envelo' . $enveloPackageNumber . '.zip';
                $this->zipModel->generate($outputFilesPaths, $zipOutputDir . '/' . $zipFilename);

                $enveloPackageNumber++;

                $spreadsheet = $this->getNewSpreadsheet($kernelRootDir);
                $enveloCounter = 0;
                $spreadsheetStartIndexFrom = 3;
                $outputFilesPaths = [];
            } else {
                $row = $this->dataRow($itemData['invoices'][0], $invoiceNumbers);
                $this->addDataRowToSpreadsheet($spreadsheet, $row, $spreadsheetStartIndexFrom);
            }

            $enveloCounter++;
            $index++;
            dump($index);
        }



        dump('Success');
    }

    private function getInvoicesFrom($y, $m, $d)
    {
        $createdDateFrom = new \DateTime();
        $createdDateFrom->setDate($y, $m, $d);
        $createdDateFrom->setTime(0, 0, 0);

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('WecodersEnergyBundle:InvoiceProforma', 'a')
            ->where('a.createdDate >= :createdDateFrom')
            ->setParameters([
                'createdDateFrom' => $createdDateFrom
            ]);

        return $q->getQuery()->getResult();
    }

    private function mergeInvoicesWithClients($invoices)
    {
        $clients = [];

        /** @var InvoiceProforma $invoice */
        foreach ($invoices as $invoice) {
            /** @var Client $client */
            $client = $invoice->getClient();
            if (!key_exists($client->getAccountNumberIdentifier()->getNumber(), $clients)) {
                $clients[$client->getAccountNumberIdentifier()->getNumber()] = [
                    'client' => $client,
                    'invoices' => [],
                ];
            }
            $clients[$client->getAccountNumberIdentifier()->getNumber()]['invoices'][] = $invoice;
        }

        return $clients;
    }

    private function dataRow($tmpInvoice, $invoiceNumbers)
    {
        /** @var InvoiceInterface $tmpInvoice */
        $fullName = $tmpInvoice->getClientFullName();
        $splittedFullName = explode(' ', $fullName);
        $surnamePart1 = isset($splittedFullName[1]) ? $splittedFullName[1] : '';
        $surnamePart2 = isset($splittedFullName[2]) ? ' ' . $splittedFullName[2] : '';
        $surnamePart3 = isset($splittedFullName[3]) ? ' ' . $splittedFullName[3] : '';

        $name = $splittedFullName[0];
        $surname = $surnamePart1 . $surnamePart2 . $surnamePart3;

        $row = [
            0 => 'Sz.P.',
            1 => '',
            2 => $name,
            3 => $surname,
            4 => '',
            5 => $tmpInvoice->getClientStreet(),
            6 => $tmpInvoice->getClientHouseNr(),
            7 => $tmpInvoice->getClientApartmentNr(),
            8 => $tmpInvoice->getClientZipCode(),
            9 => $tmpInvoice->getClientCity(),
            10 => 'Polska',
            11 => '1',
            12 => 'Y',
            13 => 'N',
            14 => 'Y',
            15 => '1',
            16 => '',
            17 => $invoiceNumbers . '.pdf',
            18 => 'Y',
            19 => 'S',
            20 => 'Y',
            21 => 'Y',
        ];

        return $row;
    }

    private function fetchInvoiceAiNumbersFromInvoices($invoices)
    {
        $invoiceNumbers = [];
        foreach ($invoices as $invoice) {
            $pieces = explode('/', $invoice->getNumber());
            $invoiceNumbers[] = $pieces[0];
        }

        return implode('-', $invoiceNumbers);
    }

    private function getInvoicesAbsoultePaths($kernelRootDir, $invoices)
    {
        $relativeInvoicesPath = \Wecoders\EnergyBundle\Service\InvoiceModel::ROOT_RELATIVE_INVOICES_PROFORMA_PATH;

        $files = [];

        /** @var InvoiceInterface, InvoicePathInterface $invoice */
        foreach ($invoices as $invoice) {
            $fullInvoicePath = $this->invoiceModel->fullInvoicePath($kernelRootDir, $invoice, $relativeInvoicesPath);
            $files[] = $fullInvoicePath . '.pdf';
        }
        return $files;
    }

    private function getNewSpreadsheet($kernelRootDir)
    {
        $kernelRootDir = $this->container->get('kernel')->getRootDir();
        return $this->getSpreadsheet($kernelRootDir . '/../var/data/envelo-template.xls');
    }

    private function addDataRowToSpreadsheet(&$spreadsheet, &$row, &$index)
    {
        $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $row[0]);
        $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $row[1]);
        $spreadsheet->getActiveSheet()->setCellValue('C' . $index, $row[2]);
        $spreadsheet->getActiveSheet()->setCellValue('D' . $index, $row[3]);
        $spreadsheet->getActiveSheet()->setCellValue('E' . $index, $row[4]);
        $spreadsheet->getActiveSheet()->setCellValue('F' . $index, $row[5]);
        $spreadsheet->getActiveSheet()->setCellValue('G' . $index, $row[6]);
        $spreadsheet->getActiveSheet()->setCellValue('H' . $index, $row[7]);
        $spreadsheet->getActiveSheet()->setCellValue('I' . $index, $row[8]);
        $spreadsheet->getActiveSheet()->setCellValue('J' . $index, $row[9]);
        $spreadsheet->getActiveSheet()->setCellValue('K' . $index, $row[10]);
        $spreadsheet->getActiveSheet()->setCellValue('L' . $index, $row[11]);
        $spreadsheet->getActiveSheet()->setCellValue('M' . $index, $row[12]);
        $spreadsheet->getActiveSheet()->setCellValue('N' . $index, $row[13]);
        $spreadsheet->getActiveSheet()->setCellValue('O' . $index, $row[14]);
        $spreadsheet->getActiveSheet()->setCellValue('P' . $index, $row[15]);
        $spreadsheet->getActiveSheet()->setCellValue('Q' . $index, $row[16]);
        $spreadsheet->getActiveSheet()->setCellValue('R' . $index, $row[17]);
        $spreadsheet->getActiveSheet()->setCellValue('S' . $index, $row[18]);
        $spreadsheet->getActiveSheet()->setCellValue('T' . $index, $row[19]);
        $spreadsheet->getActiveSheet()->setCellValue('U' . $index, $row[20]);
        $spreadsheet->getActiveSheet()->setCellValue('V' . $index, $row[21]);

        $index++;
    }

    private function saveSpreadsheet($spreadsheet, $outputFilePath)
    {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Envelo.xls"');
        header('Cache-Control: max-age=0');

        $kernelRootDir = $this->container->get('kernel')->getRootDir();
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save($outputFilePath);
    }

    private function mergePdfFiles($files, $outputFilePath)
    {
        $pdf = new Fpdi();

        // iterate through the files
        foreach ($files as $file) {
            // get the page count
            $pageCount = $pdf->setSourceFile($file);
            // iterate through all pages
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // import a page
                $templateId = $pdf->importPage($pageNo);
                // get the size of the imported page
                $size = $pdf->getTemplateSize($templateId);

                // create a page (landscape or portrait depending on the imported page size)
                if ($size['width'] > $size['height']) {
                    $pdf->AddPage('L', array($size['width'], $size['height']));
                } else {
                    $pdf->AddPage('P', array($size['width'], $size['height']));
                }

                // use the imported page
                $pdf->useTemplate($templateId);
            }
        }

        $pdf->Output($outputFilePath, 'F');
    }
}