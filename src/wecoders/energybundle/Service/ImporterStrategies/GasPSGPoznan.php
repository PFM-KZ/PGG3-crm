<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;

class GasPSGPoznan extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/gas/psg-poznan';
    protected $code = OsdModel::OPTION_GAS_PSG_POZNAN;
    protected $value = 'Gaz - PSG Poznań';

    protected $readerName = 'Csv';
    protected $delimiter = ';';
    protected $startFromRow = 2;
    protected $endColumn = 'Q';

    private $dateFormat = 'Y-m-d';

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function hydrate($rows)
    {
        $result = [];
        if (!$rows) {
            return $result;
        }

        foreach ($rows as $key => $row) {
            $energyData = new EnergyData();
            $energyData->setPpCode(str_replace(' ', '', $row[0]));
            $energyData->setTariff($row[3]);
            $energyData->setDeviceId($row[6]);
            $energyData->setStateStart($row[10]);
            $energyData->setStateEnd($row[11]);
            if ($row[8]) {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[8]));
            }
            if ($row[9]) {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[9]));
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }
            $energyData->setConsumptionM($row[12]);
            $energyData->setRatio(str_replace(',', '.', $row[13]));
            $energyData->setConsumptionKwh($row[14]);
            $readingTypeString = $row[7];
            $readingType = null;
            if ($readingTypeString == 'RZEC' || $readingTypeString == 'SZAC' || $readingTypeString == 'MON') {
                $readingType = self::TYPE_READING_REAL;
            } else { // ODB
                $readingType = self::TYPE_READING_ESTIMATE;
            }
            $energyData->setReadingType($readingType);
            $energyData->setReadingTypeOriginal($readingTypeString);
            $energyData->setEnergyType(self::TYPE_GAS_CODE);
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