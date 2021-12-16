<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class ElectricityTauron extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/tauron';
    protected $code = OsdModel::OPTION_ELECTRICITY_TAURON;
    protected $value = 'Prąd - Tauron';

    protected $readerName = 'Csv';
    protected $startFromRow = 8;
    protected $endColumn = 'R';
    private $dateFormat = 'Y-m-d';

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function load($fullPathToFile, $filename)
    {
        $this->filename = $filename;
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new StringValueBinder());
        $reader = new Csv();
        $reader->setInputEncoding('CP1250');
        $reader->setDelimiter(';');
        $rows = $this->getDataRowsUniversal($reader, $fullPathToFile, $this->startFromRow, $this->endColumn);

        if (!$rows) {
            return null;
        }
        return $rows;
    }

    public function hydrate($rows)
    {
        $result = [];
        foreach ($rows as $key => $row) {
            $energyData = new EnergyData();
            $energyData->setPpCode($row[0]);
            $energyData->setDeviceId($row[1]);
            $energyData->setOtCode($row[2]);
            $energyData->setTariff($row[3]);
            if ($row[6]) {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[6])); // ?
            }
            if ($row[7]) {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[7])); // ?
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }
            // obis version
            $energyData->setArea($row[5]);
            $energyData->setAreaOriginal($row[5]);

            if (is_string($row[8])) {
                $row[8] = str_replace(',', '.', $row[8]);
            }
            $energyData->setStateStart($row[8]);

            if (is_string($row[9])) {
                $row[9] = str_replace(',', '.', $row[9]);
            }
            $energyData->setStateEnd($row[9]);

            if (is_string($row[10])) {
                $row[10] = str_replace(',', '.', $row[10]);
            }
            $energyData->setConsumptionKwh($row[10]);

            if (is_string($row[11])) {
                $row[11] = str_replace(',', '.', $row[11]);
            }
            $energyData->setConsumptionLossKwh($row[11]);


            $energyData->setRatio($row[13]);

            $energyData->setReadingStatus($row[15]);
            $energyData->setBillingStatus($row[16]);
            $readingTypeString = $row[17];
            $readingType = null;
            if ($energyData->getReadingStatus() == 'Z' || $energyData->getReadingStatus() == 'F') { // zdalny / fizyczny
                $readingType = self::TYPE_READING_REAL;
            } else {
                $readingType = self::TYPE_READING_ESTIMATE;
            }
            $energyData->setReadingType($readingType);
            $energyData->setReadingTypeOriginal($readingTypeString);
            $energyData->setEnergyType(self::TYPE_ENERGY_CODE);
            $energyData->setFilename($this->getFilename());

            $energyData->setCode($this->code);
            $result[$key] = $energyData;
        }

        return $result;
    }

    public function validate($objects)
    {
        return [];
    }




}