<?php


namespace Wecoders\EnergyBundle\Service\Xlsx;


use PhpOffice\PhpSpreadsheet\Spreadsheet;

class XlsxWriter
{
    public function write(array $data, array $headers = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $currentRow = 1;

        if ($headers) {
            $currentColumn = 'A';
            foreach($headers as $header) {
                $sheet->setCellValue($currentColumn . $currentRow, $header);
                $currentColumn++;
            }
            $currentRow++;
        }

        foreach($data as $row) {
            $currentColumn = 'A';
            foreach($row as $value) {
                $sheet->setCellValue($currentColumn . $currentRow, $value);
                $currentColumn++;
            }
            $currentRow++;
        }

        return $spreadsheet;
    }
}