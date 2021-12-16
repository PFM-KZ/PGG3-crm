<?php

namespace Wecoders\EnergyBundle\Service;

class SpreadsheetReader
{
    public function fetchRows($readerType, $fullPathToFile, $firstDataRowIndex, $highestColumn, $readerOptions = [])
    {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($readerType);

        foreach ($readerOptions as $method => $value) {
            $reader->$method($value);
        }

        try {
            $spreadsheet = $reader->load($fullPathToFile);
        } catch (\Exception $e) {
            die('File format error: check if you choosen correct file and try again.');
        }

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
}