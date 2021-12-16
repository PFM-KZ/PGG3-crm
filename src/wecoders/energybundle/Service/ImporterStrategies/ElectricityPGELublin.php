<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;
use Wecoders\EnergyBundle\Service\TariffModel;

class ElectricityPGELublin extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/pge-lublin';
    protected $code = OsdModel::OPTION_ELECTRICITY_PGE_LUBLIN;
    protected $value = 'Prąd - PGE Lublin';

    private $dateFormat = 'Y-m-d\TH:i:sP';

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function load($fullPathToFile, $filename)
    {
        $this->filename = $filename;
        /** @var \SimpleXMLElement $xml */
        $xml = simplexml_load_file($fullPathToFile);
        $fileType = $xml->getName();
        $namespaces = $xml->getNamespaces(true);
        $xml = $xml->children($namespaces['ns2']);
        dump($xml);

        $rows = [];
        $xmlTresc = $xml->Tresc;
        $xmlLiczniki = $xmlTresc->Liczniki;
        $xmlDaneLicznika = $xmlLiczniki->DaneLicznika;

        foreach ($xmlDaneLicznika as $xmlDane) {
            foreach ($xmlDane->Wskazania->Wskazanie as $wskazanie) {
                $rows[] = [
                    0 => (string) $xmlDane->kodPPE,
                    1 => (string) $xmlDane->nrLicznika,
                    2 => (string) $wskazanie->okresRozliczeniowyOd,
                    3 => (string) $wskazanie->okresRozliczeniowyDo,
                    4 => (string) $wskazanie->strefa,
                    5 => (string) $wskazanie->wskazaniePoprzednie,
                    6 => (string) $wskazanie->wskazanieBiezace,
                    7 => (string) $wskazanie->mnozna,
                    8 => (string) $wskazanie->zuzycie,
                    9 => (string) $wskazanie->strataProcent,
                    10 => (string) $wskazanie->zuzycieZUwzglednieniemStrat,
                    11 => (string) $wskazanie->pochodzenie,
                ];
            }
        }

        if (!count($rows)) {
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
            if ($row[2]) {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[2]));
            }
            if ($row[3]) {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[3]));
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }
            $area = null;
            if ($row[4] == 'Cała doba') {
                $area = TariffModel::TARIFF_ZONE_ALL_DAY;
            } elseif ($row[4] == 'Dzienna') {
                $area = TariffModel::TARIFF_ZONE_DAY;
            } elseif ($row[4] == 'Nocna') {
                $area = TariffModel::TARIFF_ZONE_NIGHT;
            }
            $energyData->setArea($area);
            $energyData->setAreaOriginal($row[4]);
            $energyData->setTariff('#replace#');
            $energyData->setStateStart($row[5]);
            $energyData->setStateEnd($row[6]);
            $energyData->setRatio($row[7]);
            $energyData->setConsumptionKwh($row[8]);
            $energyData->setConsumptionIncludingLoss($row[10]);
//            $energyData->setConsumptionCorrection($row[8]);
            $readingTypeString = $row[11];
            $readingType = null;
            if ($readingTypeString == 'F' || $readingTypeString == 'Z') {
                $readingType = self::TYPE_READING_REAL;
            } elseif ($readingTypeString == self::TYPE_READING_ESTIMATE) {
                $readingType = self::TYPE_READING_ESTIMATE;
            } elseif ($readingTypeString == self::TYPE_READING_RECIPIENT) {
                $readingType = self::TYPE_READING_RECIPIENT;
            }
            $energyData->setReadingType($readingType);
            $energyData->setReadingTypeOriginal($readingTypeString);
            $energyData->setEnergyType(self::TYPE_ENERGY_CODE);
            $energyData->setFilename($this->getFilename());
//            $energyData->setFileType($row[14]);
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