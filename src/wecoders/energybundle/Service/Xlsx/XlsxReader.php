<?php


namespace Wecoders\EnergyBundle\Service\Xlsx;


class XlsxReader
{
    public function read($filePath, $startRow = 1)
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
        $spreadsheet = $reader->load($filePath);

        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        $rows = [];

        for ($row = $startRow; $row <= $highestRow; ++$row) {
            $rows[$row] = [];
            for ($col = 'A'; $col <= $highestColumn; ++$col) {
                $rows[$row][] = $worksheet->getCell($col . $row)->getFormattedValue();
            }
        }

        return array_values($rows);
    }
}