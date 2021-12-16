<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;
use Wecoders\EnergyBundle\Service\TariffModel;

class ElectricityPGERzeszow extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/pge-rzeszow';
    protected $code = OsdModel::OPTION_ELECTRICITY_PGE_RZESZOW;
    protected $value = 'Prąd - PGE Rzeszów';

    protected $readerName = 'Xlsx';
    protected $startFromRow = 4;
    protected $endColumn = 'T';
    private $dateFormat = 'Y-m-d';

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function load($fullPathToFile, $filename)
    {
        $this->filename = $filename;
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

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
            $energyData->setPpCode($row[2]);
            $energyData->setDeviceId($row[7]);
            $energyData->setTariff($row[3]);
            if ($row[10]) {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[10])); // ?
            }
            if ($row[11]) {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[11])); // ?
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }
            // obis version
            if ($row[13] == 0) {
                $area = TariffModel::TARIFF_ZONE_ALL_DAY;
            } elseif ($row[13] == 1) {
                $area = TariffModel::TARIFF_ZONE_DAY;
            } elseif ($row[13] == 2) {
                $area = TariffModel::TARIFF_ZONE_NIGHT;
            } else {
                die('Brak wartości w kolumnie "Nr strefy"');
            }
            $energyData->setArea($area);
            $energyData->setAreaOriginal($row[13]);

            $energyData->setStateStart($row[14]);
            $energyData->setStateEnd($row[15]);
            $energyData->setRatio($row[16]);

            $energyData->setConsumptionLossKwh($row[18]);
            $energyData->setConsumptionKwh($row[19]);
            $readingTypeString = $row[9];
            $readingType = null;
            if ($readingTypeString == 'R') { // Rzeczywisty
                $readingType = self::TYPE_READING_REAL;
            } elseif ($readingTypeString == 'P') { // Prognozowany
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