<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;

class GasPSGPoznanV2 extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/gas/poznan-v2';
    protected $code = OsdModel::OPTION_GAS_PSG_POZNAN_V2;
    protected $value = 'Gaz - PSG Poznań od 2020 (nowy format)';

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
            $energyData->setPpCode($row[3]);
//            $energyData->setDeviceId($row[1]); // ?
//            $energyData->setReadingId($row[2]);
            // 3 - empty column "IdScada"
//            $energyData->setArea($row[4]);
//            $energyData->setSeller($row[5]);
            $energyData->setTariff($row[5]);
//            $energyData->setClientName($row[7]);
//            $energyData->setClientAddress($row[8]);
//            $energyData->setDevice($row[9]);
    //            $energyData->setDeviceSerialNumber($row[10]);
            $energyData->setDeviceId($row[6]);
            $energyData->setStateStart(0);
            $energyData->setStateEnd($row[9]);
            $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[8])); // ?
            if ($energyData->getBillingPeriodTo() === false) {
                throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
            }
            $energyData->setConsumptionM($row[10]);
            $energyData->setRatio(str_replace(',', '.', $row[12]));
            $energyData->setConsumptionKwh($row[11]);
            $readingTypeString = $row[13];
            $readingType = null;
            if ($readingTypeString == 'odczyt rzeczywisty') { // ?? KON
                $readingType = self::TYPE_READING_REAL;
            } elseif ($readingTypeString == 'odczyt szacowany') { // ????
                $readingType = self::TYPE_READING_ESTIMATE;
            }
            $energyData->setReadingType($readingType);
            $energyData->setReadingTypeOriginal($readingTypeString);
            // 18 - date of write to operator system date of reading
            // 19 - contract number
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