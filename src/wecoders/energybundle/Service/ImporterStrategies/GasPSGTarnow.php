<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;

class GasPSGTarnow extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/gas/tarnow';
    protected $code = OsdModel::OPTION_GAS_PSG_TARNOW;
    protected $value = 'Gaz - PSG Tarnów';

    protected $readerName = 'Xlsx';
    protected $startFromRow = 2;
    protected $endColumn = 'T';

    private $dateFormat = 'Y-m-d H:i:s:u';

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function hydrate($rows)
    {
        $result = [];
        foreach ($rows as $key => $row) {
            $energyData = new EnergyData();
            $energyData->setPpCode($row[1]);
//            $energyData->setDeviceId($row[1]); // ?
//            $energyData->setReadingId($row[2]);
            // 3 - empty column "IdScada"
//            $energyData->setArea($row[4]);
//            $energyData->setSeller($row[5]);
            $energyData->setTariff($row[6]);
//            $energyData->setClientName($row[7]);
//            $energyData->setClientAddress($row[8]);
//            $energyData->setDevice($row[9]);
    //            $energyData->setDeviceSerialNumber($row[10]);
            $energyData->setDeviceId($row[10]);
            $energyData->setStateStart($row[11]);
            $energyData->setStateEnd($row[12]);
            $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[13])); // ?
            if ($energyData->getBillingPeriodTo() === false) {
                throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
            }
            $energyData->setConsumptionM($row[14]);
            $energyData->setRatio($row[15]);
            $energyData->setConsumptionKwh($row[16]);
            $readingTypeString = $row[17];
            $readingType = null;
            if ($readingTypeString == 'RZECZ' || $readingTypeString == 'KON') { // ?? KON
                $readingType = self::TYPE_READING_REAL;
            } elseif ($readingTypeString == 'SZAC') { // ????
                $readingType = self::TYPE_READING_ESTIMATE;
            } elseif ($readingTypeString == 'ODB') { // ????
                $readingType == self::TYPE_READING_RECIPIENT;
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