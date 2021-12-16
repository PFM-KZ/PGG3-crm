<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;

class ElectricityEnerga extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/energa';
    protected $code = OsdModel::OPTION_ELECTRICITY_ENERGA;
    protected $value = 'Prąd - Energa';

    private $dateFormat = 'Y-m-d';

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


        $rows = [];
        $xmlTresc = $xml->Tresc;
        $xmlLiczniki = $xmlTresc->Liczniki;
        $xmlDaneLicznika = $xmlLiczniki->DaneLicznika;
        if ((string) $xml->Naglowek->typDokumentu == 'UDPP') {
            foreach ($xmlDaneLicznika as $xmlDane) {
                foreach ($xmlDane->Wskazania->Wskazanie as $wskazanie) {
                    $rows[] = [
                        0 => (string) $xmlDane->kodPPE,
                        1 => (string) $xmlDane->nrLicznika,
                        2 => (string) $wskazanie->kodOT,
                        3 => null,
                        4 => null,
                        5 => (string) $wskazanie->dataWskazania,
                        6 => (string) $wskazanie->obis,
                        7 => (string) $wskazanie->rozklad,
                        8 => '0',
                        9 => (string) $wskazanie->wskazanieBiezace,
                        10 => (string) $wskazanie->mnozna,
                        11 => '0',
                        12 => (string) $wskazanie->strataProcent,
                        13 => null,
                        14 => null,
                        15 => (string) $wskazanie->statusWskazania,
                        16 => (string) $xmlDane->typDokumentu,
                    ];
                }
            }
        } else {
            foreach ($xmlDaneLicznika as $xmlDane) {
                foreach ($xmlDane->Wskazania->Wskazanie as $wskazanie) {
                    $rows[] = [
                        0 => (string) $xmlDane->kodPPE,
                        1 => (string) $xmlDane->nrLicznika,
                        2 => (string) $wskazanie->kodOT,
                        3 => (string) $wskazanie->kodOTP,
                        4 => (string) $wskazanie->okresRozliczeniowyOd,
                        5 => (string) $wskazanie->okresRozliczeniowyDo,
                        6 => (string) $wskazanie->obis,
                        7 => (string) $wskazanie->rozklad,
                        8 => (string) $wskazanie->wskazaniePoprzednie,
                        9 => (string) $wskazanie->wskazanieBiezace,
                        10 => (string) $wskazanie->mnozna,
                        11 => (string) $wskazanie->zuzycie_Pmax,
                        12 => (string) $wskazanie->strataProcent,
                        13 => (string) $wskazanie->zuzycieZUwzglednieniemStrat,
                        14 => (string) $wskazanie->korektaZuzycia,
                        15 => (string) $wskazanie->statusWskazania,
                        16 => (string) $xmlDane->typDokumentu,
                    ];
                }
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
//            $energyData->setOtCode($row[2]);
//            $energyData->setOtpCode($row[3]);
            if ($row[4]) {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[4])); // ?
            }
            if ($row[5]) {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[5])); // ?
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }
//            $energyData->setObis($row[6]);
            $energyData->setArea($row[6]);
            $energyData->setTariff($row[7]);
            $energyData->setStateStart($row[8]);
            $energyData->setStateEnd($row[9]);
            $energyData->setRatio($row[10]);
            $energyData->setConsumptionKwh($row[11]);
//            $energyData->setLossPercentage($row[12]);
            $energyData->setConsumptionIncludingLoss($row[13]);
            $energyData->setConsumptionCorrection($row[14]);
            $readingTypeString = $row[15];
            $readingType = null;
            if ($readingTypeString == self::TYPE_READING_REAL) {
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
            $energyData->setFileType($row[16]);
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