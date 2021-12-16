<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\ContractModel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class ChangePpCodesTauronCommand extends Command
{
    private $em;
    private $contractModel;
    private $container;
    private $spreadsheetReader;

    public function __construct(
        EntityManager $em,
        ContractModel $contractModel,
        ContainerInterface $container,
        SpreadsheetReader $spreadsheetReader
    )
    {
        $this->em = $em;
        $this->contractModel = $contractModel;
        $this->container = $container;
        $this->spreadsheetReader = $spreadsheetReader;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:change-pp-codes-tauron');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();

        $fileActual = $kernelRootDir . '/../var/data/pp-change/aktualne_kody_ppe_do_zmiany.xlsx';
        $rows = $this->spreadsheetReader->fetchRows('Xlsx', $fileActual, 2, 'C');
        // hydrate
        $data = [];
        dump('Sprawdzanie aktualnych kodów...');
        foreach ($rows as $row) {
            if (isset($data[$row[1]])) {
                dump('Duplikat: ' . $row[1]);
            }

            $data[$row[1]] = $row;
        }


        $file = $kernelRootDir . '/../var/data/pp-change/nowe_kody_ppe.txt';
        $contents = file_get_contents($file);
        $lines = explode("\n", $contents); // this is your array of words

        dump('Przypisywanie nowych wartości...');
        $index = 1;
        foreach($lines as $line) {
            $row = explode(';', $line);
            if (array_key_exists($row[0], $data)) {
                $data[$row[0]][2] = str_replace("\r", '', $row[1]);
            }
            $index++;
        }

        dump('Sprawdzanie przypisanych wartości');
        $assigned = 0;
        $notAssignedList = [];
        foreach ($data as $key => $item) {
            if ($item[2]) {
                $assigned++;
            } else {
                $notAssignedList[] = $key;
            }
        }

        dump('Ilość: ' . count($data));
        dump('Przypisanych: ' . $assigned);
        dump('Pominiętych: ' . count($notAssignedList));
        if (count($notAssignedList)) {
            dump('Lista pominiętych');
            foreach ($notAssignedList as $item) {
                dump($item);
            }
        }

        //
        dump('Eksport kodów');
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Unikalny numer rachunku klienta');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'Dotychczasowy kod PPE');
        $spreadsheet->getActiveSheet()->setCellValue('C1', 'Nowy kod PPE');

        $index = 2;
        foreach ($data as $item) {

            $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $item[0]);
            $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $item[1]);
            $spreadsheet->getActiveSheet()->setCellValueExplicit('C' . $index, $item[2], DataType::TYPE_STRING);

            $index++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($kernelRootDir . '/../var/data/pp-change/aktualne_kody_ppe_do_zmiany_zaktualizowane.xlsx');



        dump('Success');
    }

}