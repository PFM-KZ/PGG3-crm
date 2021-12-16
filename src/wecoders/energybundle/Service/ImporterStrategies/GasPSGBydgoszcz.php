<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;

class GasPSGBydgoszcz extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/gas/psg-bydgoszcz';
    protected $code = 'gas-psg-bydgoszcz';
    protected $value = 'Gaz - PSG Bydgoszcz';

    protected $readerName = 'Xlsx';
    protected $startFromRow = 2;
    protected $endColumn = 'M';

    private $dateFormat = 'Y-m-d';

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function hydrate($rows)
    {
        die('disabled');
        $result = [];
        foreach ($rows as $key => $row) {
            $energyData = new EnergyData();
//            $energyData->setSeller($row[0]);
            $energyData->setPpCode($row[1]);
            $energyData->setDeviceId($row[2]); // ?
            $energyData->setTariff($row[3]);
            $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[4])); // ?
            $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[5])); // ?
            if ($energyData->getBillingPeriodTo() === false) {
                throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
            }
            $energyData->setStateStart($row[6]);
            $energyData->setStateEnd($row[7]);
            $energyData->setConsumptionM($row[8]);
            $energyData->setMultiplier($row[9]);
            $energyData->setConsumptionKwh($row[10]);
//            $readingTypeString = $row[11];
//            $readingType = null;
//            if ($readingTypeString == 'Rozliczeniowy') {
//                $readingType = self::TYPE_READING_REAL;
//            } elseif ($readingTypeString == 'Szacunkowy') { // ????
//                $readingType = self::TYPE_READING_ESTIMATE;
//            } elseif ($readingTypeString == 'Odbiorcy') { // ????
//                $readingType == self::TYPE_READING_RECIPIENT;
//            }
//            $energyData->setReadingType($readingType);
            // 12 - "Szacunkowy" ? Tak / Nie ?????
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