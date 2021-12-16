<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;

abstract class Energy extends EnergyData
{
    const TYPE_ENERGY_CODE = 'EE';
    const TYPE_GAS_CODE = 'GS';

    const TYPE_READING_REAL = 'R';
    const TYPE_READING_ESTIMATE = 'S';
    const TYPE_READING_RECIPIENT = 'O';

    /** @var  EntityManager */
    protected $em;
    /** @var  EnergyDataModel */
    protected $energyDataModel;

    protected $filename;
    protected $uploadDirectory;
    protected $code;
    protected $value;

    protected $readerName;
    protected $delimiter;
    protected $startFromRow;
    protected $endColumn;

    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getAbsoluteUploadDirectoryPath($kernelRootDir)
    {
        return $kernelRootDir . '/../' . $this->uploadDirectory;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function load($fullPathToFile, $filename)
    {
        $this->filename = $filename;
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($this->readerName);
        if ($this->delimiter) {
            $reader->setDelimiter(';');
        }
        $rows = $this->getDataRowsUniversal($reader, $fullPathToFile, $this->startFromRow, $this->endColumn);

        if (!$rows) {
            return null;
        }
        return $rows;
    }

    public function save($objects)
    {
        /** @var EnergyData $object */
        foreach ($objects as $object) {
            $this->em->persist($object);
            $this->em->flush();
        }
    }

    protected function getDataRowsUniversal($reader, $file, $firstDataRowIndex, $highestColumn)
    {
        try {
            $spreadsheet = $reader->load($file);
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

    protected function removeMicrosecondsFromDateTimeStringFormat($string, $delimiter)
    {
        $pos = strrpos($string, $delimiter);
        if ($pos !== false) {
            return substr($string, 0, $pos);
        }

        return $string;
    }
}