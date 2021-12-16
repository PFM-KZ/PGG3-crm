<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class ElectricityPGEWarszawa extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/pge-warszawa';
    protected $code = OsdModel::OPTION_ELECTRICITY_PGE_WARSZAWA;
    protected $value = 'Prąd - PGE Warszawa';

    protected $readerName = 'Csv';
    protected $startFromRow = 4;
    protected $endColumn = 'R';
    private $dateFormat = 'Y-m-d H:i:s';

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function load($fullPathToFile, $filename)
    {
        $this->filename = $filename;
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
            if ($row[3]) {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[3])); // ?
            }
            if ($row[4]) {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[4])); // ?
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }

            $energyData->setTariff('#replace#');

            // todo: guess area - must be in specification
            $energyData->setArea($row[5]);
            $energyData->setAreaOriginal($row[5]);

            $energyData->setStateStart($row[6]);
            $energyData->setStateEnd($row[7]);
            $energyData->setRatio($row[8]);

            $energyData->setConsumptionKwh($row[9]);
            $readingTypeString = $row[2];
            // todo: guess reading type - must be in specification
            $readingType = null;
            if ($readingTypeString == self::TYPE_READING_REAL) {
                $readingType = self::TYPE_READING_REAL;
            } elseif ($readingTypeString == self::TYPE_READING_ESTIMATE) {
                $readingType = self::TYPE_READING_ESTIMATE;
            } elseif ($readingTypeString == self::TYPE_READING_RECIPIENT) {
                $readingType = self::TYPE_READING_RECIPIENT;
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