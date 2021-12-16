<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use SplFileObject;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;

class GasPSGWarszawa extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/gas/psg-warszawa';
    protected $code = OsdModel::OPTION_GAS_PSG_WARSZAWA;
    protected $value = 'Gaz - PSG Warszawa';

    protected $readerName = null;
    protected $startFromRow = null;
    protected $endColumn = null;

    private $dateFormat = 'Y-m-d-H.i.s';

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function load($fullPathToFile, $filename)
    {
        $this->filename = $filename;
        $file = new SplFileObject($fullPathToFile);
        $rows = [];
        while (!$file->eof()) {
            $exploded = explode('|', $file->fgets());
            if (count($exploded) > 1) {
                $rows[] = $exploded;
            }
        }
        $file = null;

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
            $energyData->setPpCode($row[4]);
//            $energyData->setDeviceId($row[1]); // ?
//            $energyData->setReadingId($row[2]);
            // 3 - empty column "IdScada"
//            $energyData->setArea($row[4]);
//            $energyData->setSeller($row[5]);
            $energyData->setTariff($row[13]);
//            $energyData->setClientName($row[7]);
//            $energyData->setClientAddress($row[8]);
//            $energyData->setDevice($row[9]);
    //            $energyData->setDeviceSerialNumber($row[10]);
            $energyData->setDeviceId($row[14]);
            $energyData->setStateStart($row[16]);
            $energyData->setStateEnd($row[18]);
            $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[19])); // ?
            if ($energyData->getBillingPeriodTo() === false) {
                throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
            }
            $energyData->setConsumptionM($row[23]);
            $energyData->setRatio($row[34]);
            $energyData->setConsumptionKwh($row[31]);
            $readingTypeString = $row[35];
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