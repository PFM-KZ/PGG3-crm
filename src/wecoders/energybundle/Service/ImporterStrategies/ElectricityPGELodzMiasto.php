<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;
use Wecoders\EnergyBundle\Service\TariffModel;

class ElectricityPGELodzMiasto extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/pge-lodz-miasto';
    protected $code = OsdModel::OPTION_ELECTRICITY_PGE_LODZ_MIASTO;
    protected $value = 'Prąd - PGE Łódź miasto';

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
            $energyData->setTariff($row[5]);
            if ($row[3]) {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[3])); // ?
            }
            if ($row[4]) {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[4])); // ?
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }
            // obis version
            $area = null;
            if ($row[6] == 'Cała Doba') {
                $area = TariffModel::TARIFF_ZONE_ALL_DAY;
            } elseif ($row[6] == 'Szczyt') {
                $area = TariffModel::TARIFF_ZONE_PEAK;
            } elseif ($row[6] == 'Pozaszczyt') {
                $area = TariffModel::TARIFF_ZONE_OFF_PEAK;
            } elseif ($row[6] == 'Dzień') {
                $area = TariffModel::TARIFF_ZONE_DAY;
            } elseif ($row[6] == 'Noc') {
                $area = TariffModel::TARIFF_ZONE_NIGHT;
            } elseif ($row[6] == 'Szczyt Przedpołudniowy') {
                $area = TariffModel::TARIFF_ZONE_MORNING_PEAK;
            } elseif ($row[6] == 'Szczyt Popołudniowy') {
                $area = TariffModel::TARIFF_ZONE_AFTERNOON_PEAK;
            } elseif ($row[6] == 'Pozostałe Godziny Doby') {
                $area = TariffModel::TARIFF_ZONE_REMAINING_HOURS_OF_DAY;
            } elseif ($row[6] == 'Godziny Doliny Obciążenia') {
                $area = null; // todo: what is this?
            }
            $energyData->setArea($area);
            $energyData->setAreaOriginal($row[6]);

            $energyData->setStateStart($row[7]);
            $energyData->setStateEnd($row[8]);
            $energyData->setRatio($row[9]);

            $energyData->setConsumptionKwh($row[10]);
            $energyData->setConsumptionIncludingLoss($row[12]);
            $readingTypeString = $row[14];
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

    public static function splitByAreas(EnergyData $energyData)
    {
        $result = [];
        $areas = [];

        $tariff = $energyData->getTariff();

        $allDay = ['G11', 'G11p', 'C11', 'C11p', 'C11o', 'C21', 'B11', 'B21', 'A21'];
        $dayAndNight = ['G12', 'G12p', 'G12as', 'C12b', 'C12bp', 'C22b', 'B12'];
        $peakAndOffPeak = ['G12w', 'C12a', 'C12ap', 'C22a', 'C22w', 'B22'];
        $morningPeakAfternoonPeakAndRest = ['B23', 'A23'];

        if (in_array($tariff, $allDay)) {
            $areas[TariffModel::getOptionByValue(TariffModel::TARIFF_ZONE_ALL_DAY)] = $energyData->getConsumptionSplitAllDay();
        } elseif (in_array($tariff, $dayAndNight)) {
            $areas[TariffModel::getOptionByValue(TariffModel::TARIFF_ZONE_DAY)] = $energyData->getConsumptionSplitDay();
            $areas[TariffModel::getOptionByValue(TariffModel::TARIFF_ZONE_NIGHT)] = $energyData->getConsumptionSplitNight();
        } elseif (in_array($tariff, $peakAndOffPeak)) {
            $areas[TariffModel::getOptionByValue(TariffModel::TARIFF_ZONE_PEAK)] = $energyData->getConsumptionSplitPeak();
            $areas[TariffModel::getOptionByValue(TariffModel::TARIFF_ZONE_OFF_PEAK)] = $energyData->getConsumptionSplitOffPeak();
        } elseif (in_array($tariff, $morningPeakAfternoonPeakAndRest)) {
            $areas[TariffModel::getOptionByValue(TariffModel::TARIFF_ZONE_MORNING_PEAK)] = $energyData->getConsumptionSplitMorningPeak();
            $areas[TariffModel::getOptionByValue(TariffModel::TARIFF_ZONE_AFTERNOON_PEAK)] = $energyData->getConsumptionSplitAfternoonPeak();
            $areas[TariffModel::getOptionByValue(TariffModel::TARIFF_ZONE_REMAINING_HOURS_OF_DAY)] = $energyData->getConsumptionSplitRemainingHoursOfDay();
        }

        $index = 0;
        foreach ($areas as $area => $consumption) {
            $newEnergyData = clone $energyData;

            $stateStart = null;
            $stateEnd = null;
            if ($index == 0) {
                $stateStart = $energyData->getStateStartSplitFirstPart();
                $stateEnd = $energyData->getStateEndSplitFirstPart();
            } elseif ($index == 1) {
                $stateStart = $energyData->getStateStartSplitSecondPart();
                $stateEnd = $energyData->getStateEndSplitSecondPart();
            } elseif ($index == 2) {
                $stateStart = $energyData->getStateStartSplitThirdPart();
                $stateEnd = $energyData->getStateEndSplitThirdPart();
            } else {
                throw new \Exception('Not expected value');
            }

            // those 4 parameters must be set later
            $newEnergyData->setConsumptionKwh($consumption);
            $newEnergyData->setArea($area);
            $newEnergyData->setStateStart($stateStart);
            // virtual product is dynamically created with 0 consumption, state start and state end must be the same
            if (isset($newEnergyData->isVirtual) && $newEnergyData->isVirtual) {
                $newEnergyData->setStateEnd($stateStart);
            } else {
                $newEnergyData->setStateEnd($stateEnd);
            }

            $result[] = $newEnergyData;
            $index++;
        }

        return $result;
    }

}