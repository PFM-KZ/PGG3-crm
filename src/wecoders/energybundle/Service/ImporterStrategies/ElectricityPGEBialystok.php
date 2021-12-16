<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Wecoders\EnergyBundle\Service\TariffModel;

class ElectricityPGEBialystok extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/pge-bialystok';
    protected $code = OsdModel::OPTION_ELECTRICITY_PGE_BIALYSTOK;
    protected $value = 'Prąd - PGE Białystok';

    protected $readerName = 'Csv';
    protected $startFromRow = 4;
    protected $endColumn = 'P';
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

            if (trim($row[5]) == 'Cala doba') {
                $area = TariffModel::TARIFF_ZONE_ALL_DAY;
            } elseif (trim($row[5]) == 'dzien') {
                $area = TariffModel::TARIFF_ZONE_DAY;
            } elseif (trim($row[5]) == 'noc') {
                $area = TariffModel::TARIFF_ZONE_NIGHT;
            } else {
                die('Nieznana strefa: ' . trim($row[5]));
            }

            $energyData->setArea($area);
            $energyData->setAreaOriginal($row[5]);

            $energyData->setStateStart($row[6]);
            $energyData->setStateEnd($row[7]);
            $energyData->setRatio($row[8]);

            $energyData->setConsumptionKwh($row[9]);
            $readingTypeString = $row[14];

            $readingType = null;
            if ($readingTypeString == 'Z' || $readingTypeString == 'F') { // zdalny / fizyczny
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