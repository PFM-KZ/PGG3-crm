<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\TimeZone;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;
use Wecoders\EnergyBundle\Service\TariffModel;

class ElectricityPGELodzTeren extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/pge-lodz-teren';
    protected $code = OsdModel::OPTION_ELECTRICITY_PGE_LODZ_TEREN;
    protected $value = 'Prąd - PGE Łódź teren';

    protected $readerName = 'Xlsx';
    protected $startFromRow = 3;
    protected $endColumn = 'BA';
    private $dateFormat = 'd/m/Y';

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
            $energyData->setTariff($row[22]);
            $energyData->setPpCode($row[34]);
            if ($row[43]) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[43], 'Europe/Warsaw');
                $energyData->setBillingPeriodFrom($date); // ?
            }
            if ($row[38]) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[38], 'Europe/Warsaw');
                $energyData->setBillingPeriodTo($date); // ?
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }

            $energyData->setRatio($row[39]);
            $energyData->setDeviceId($row[23]);

            $estimatedCodes = [78, 79, 80];
            if (in_array($row[24], $estimatedCodes)) {
                $energyData->setReadingType(self::TYPE_READING_ESTIMATE);
            } else {
                $energyData->setReadingType(self::TYPE_READING_REAL);
            }

            $energyData->setReadingTypeOriginal($row[24]);

            $energyData->setConsumptionSplitAllDay($row[25]);
            $energyData->setConsumptionSplitPeak($row[26]);
            $energyData->setConsumptionSplitOffPeak($row[27]);
            $energyData->setConsumptionSplitDay($row[28]);
            $energyData->setConsumptionSplitNight($row[29]);
            $energyData->setConsumptionSplitMorningPeak($row[30]);
            $energyData->setConsumptionSplitAfternoonPeak($row[31]);
            $energyData->setConsumptionSplitRemainingHoursOfDay($row[32]);

            $energyData->setStateStartSplitFirstPart($row[40]);
            $energyData->setStateStartSplitSecondPart($row[41]);
            $energyData->setStateStartSplitThirdPart($row[42]);

            $energyData->setStateEndSplitFirstPart($row[35]);
            $energyData->setStateEndSplitSecondPart($row[36]);
            $energyData->setStateEndSplitThirdPart($row[37]);

            // those 4 parameters must be set later
            $energyData->setConsumptionKwh(0);
            $energyData->setArea(null);
            $energyData->setStateStart(0);
            $energyData->setStateEnd(0);

            $energyData->setEnergyType(self::TYPE_ENERGY_CODE);
            $energyData->setFilename($this->getFilename());
            $energyData->setCode($this->code);

            $result[] = $energyData;
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