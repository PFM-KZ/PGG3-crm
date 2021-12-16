<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;

class GasPSGGdansk extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/gas/psg-gdansk';
    protected $code = OsdModel::OPTION_GAS_PSG_GDANSK;
    protected $value = 'Gaz - PSG Gdańsk';

    protected $readerName = 'Xlsx';
    protected $startFromRow = 2;
    protected $endColumn = 'N';

    private $dateFormat = 'd/m/Y';

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
            // 0 - title (Gazownia w gdansku)
//            $energyData->setSeller($row[1]);
            $energyData->setTariff($row[2]);
            $energyData->setPpCode($row[3]);
            // 4 - RO - calculation year???
            $energyData->setBillingPeriodFrom($row[5] ? \DateTime::createFromFormat($this->dateFormat, $row[5]) : null); // ?
            $energyData->setStateStart($row[6]);
            // 7 - RO2 ????
            $energyData->setBillingPeriodTo($row[8] ? \DateTime::createFromFormat($this->dateFormat, $row[8]) : null); // ?
            if ($energyData->getBillingPeriodTo() === false) {
                throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
            }
            $energyData->setStateEnd($row[9]);
            $energyData->setRatio($row[10]);
            $energyData->setDeviceId($row[11]);
            $energyData->setConsumptionM($row[12]);
            $energyData->setConsumptionKwh($row[13]);

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