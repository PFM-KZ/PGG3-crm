<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\SellerModel;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;

class ElectricityEnea extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/enea';
    protected $code = OsdModel::OPTION_ELECTRICITY_ENEA;
    protected $value = 'Prąd - Enea';

    private $dateFormat = 'Y-m-d\TH:i:s';

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

//        dump($xml);

        $rows = [];
        $xmlNaglowek = $xml->Naglowek;

        if ($fileType == 'UDPS') {
            foreach ($xml->Odczyty as $xmlDane) {

                foreach ($xmlDane->Odczyty->DaneOdczytowe as $daneOdczytowe){
                    $rows[] = [
                        0 => (string)$xmlDane->PPE,
                        1 => (string)$daneOdczytowe->DCPO,
                        2 => (string)$daneOdczytowe->DCKO,
                        3 => (string)$daneOdczytowe->NL,
                        4 => (string)$daneOdczytowe->M,
                        5 => (string)$daneOdczytowe->WCPO,
                        6 => (string)$daneOdczytowe->WCKO,
                        7 => (string)$daneOdczytowe->ER,
                        8 => (string)$daneOdczytowe->KER,
                        9 => (string)$daneOdczytowe->SER,
                        10 => (string)$daneOdczytowe->OBIS,
                        11 => (string)$daneOdczytowe->SR,
                        12 => (string)$xmlDane->Odczyty->Umowa->T,
                        13 => (string)$xmlDane->Odczyty->Umowa->OR,
                        14 => $fileType,
                    ];
                }
            }
        } elseif ($fileType == 'IOZ') {
            foreach ($xml->Rozliczenia as $xmlDane) {
                foreach ($xmlDane->Rozliczenie->DaneRozliczenia as $daneRozliczenia) {
                    if (!count($daneRozliczenia->DaneOdczytowe)) {
                        // todo: Dane Rozliczeniowe
                        continue;
                    }
                    foreach ($daneRozliczenia->DaneOdczytowe as $daneOdczytowe){
                        $rows[] = [
                            0 => (string)$xmlDane->PPE,
                            1 => (string)$daneOdczytowe->DCPO,
                            2 => (string)$daneOdczytowe->DCKO,
                            3 => (string)$daneOdczytowe->NL,
                            4 => (string)$daneOdczytowe->M,
                            5 => (string)$daneOdczytowe->WCPO,
                            6 => (string)$daneOdczytowe->WCKO,
                            7 => (string)$daneOdczytowe->ER,
                            8 => (string)$daneOdczytowe->KER,
                            9 => (string)$daneOdczytowe->SER,
                            10 => (string)$daneOdczytowe->OBIS,
                            11 => (string)$daneOdczytowe->SR,
                            12 => (string)$xmlDane->Rozliczenie->Umowa->T,
                            13 => (string)$xmlDane->Rozliczenie->Umowa->OR,
                            14 => $fileType,
                        ];
                    }
                }
            }
        } else {
            die('File type ' . $fileType . ' is not supported. Supported file types: IOZ, UDPS.');
        }

        if (!count($rows)) {
            return null;
        }

        return $rows;
    }

    public function hydrate($rows)
    {
        $indexOfDuplicates = 0;
        $result = [];
        foreach ($rows as $key => $row) {

            if ($this->em->getRepository('Wecoders\EnergyBundle\Entity\EnergyData')->findOneBy([
                'ppCode' => $row[0],
                'stateStart' => $row[5],
                'stateEnd' => $row[6],
                'billingPeriodFrom' => \DateTime::createFromFormat($this->dateFormat, $row[1]),
                'billingPeriodTo' => \DateTime::createFromFormat($this->dateFormat, $row[2])
            ])) {
                $indexOfDuplicates++;
                continue;
            }

            $energyData = new EnergyData();
            $energyData->setPpCode($row[0]);
            $energyData->setDeviceId($row[3]);
            if ($row[1]) {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $row[1])); // ?
            }
            if ($row[2]) {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $row[2])); // ?
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            }
            $energyData->setArea($row[10]);
            $energyData->setAreaOriginal($row[10]);
            $energyData->setTariff($row[12]);
            $energyData->setStateStart($row[5]);
            $energyData->setStateEnd($row[6]);
            $energyData->setRatio($row[4]);
            $energyData->setConsumptionKwh($row[7]);
            $energyData->setConsumptionIncludingLoss($row[9]);
            $energyData->setConsumptionCorrection($row[8]);
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
            $energyData->setFileType($row[14]);
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