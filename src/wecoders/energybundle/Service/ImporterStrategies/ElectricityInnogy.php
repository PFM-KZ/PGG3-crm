<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class ElectricityInnogy extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/innogy';
    protected $code = OsdModel::OPTION_ELECTRICITY_INNOGY;
    protected $value = 'Prąd - Innogy';

    protected $readerName = 'Csv';
    protected $startFromRow = 11;
    protected $endColumn = 'U';
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
            $energyData->setTariff($row[2]);
            if ($row[5]) {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[5])); // ?
            }
            if ($row[6]) {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[6])); // ?
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }
            // obis version
            $energyData->setArea($row[4]);
            $energyData->setAreaOriginal($row[4]);

            $energyData->setStateStart($row[7]);
            $energyData->setStateEnd($row[10]);
            $energyData->setRatio($row[13]);

            $energyData->setConsumptionKwh($row[14]);
            $readingTypeString = $row[12];
            $readingType = null;
            if ($readingTypeString == 'F' || $readingTypeString == 'Z') { // fizyczny / zdalny
                $readingType = self::TYPE_READING_REAL;
            } elseif ($readingTypeString == self::TYPE_READING_ESTIMATE) { // S szacowany
                $readingType = self::TYPE_READING_ESTIMATE;
            } elseif ($readingTypeString == self::TYPE_READING_RECIPIENT) { // O przez odbiorce
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