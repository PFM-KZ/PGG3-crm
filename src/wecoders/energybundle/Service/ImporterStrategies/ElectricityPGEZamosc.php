<?php

namespace Wecoders\EnergyBundle\Service\ImporterStrategies;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\EnergyDataModel;
use Wecoders\EnergyBundle\Service\OsdModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Wecoders\EnergyBundle\Service\TariffModel;

class ElectricityPGEZamosc extends Energy implements ImporterStrategyInterface
{
    protected $uploadDirectory = 'var/data/wecoders/energybundle/data/electricity/pge-zamosc';
    protected $code = OsdModel::OPTION_ELECTRICITY_PGE_ZAMOSC;
    protected $value = 'Prąd - PGE Zamość';

    protected $readerName = 'Csv';
    protected $startFromRow = 1;
    protected $endColumn = 'B';
    private $dateFormat = 'Y-m-d H:i:s';

    /** @var  EnergyData */
    private $currentItem;

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function load($fullPathToFile, $filename)
    {
        $this->filename = $filename;
        $reader = new Csv();
        $reader->setInputEncoding('CP1250');
        $reader->setDelimiter(';');
        $rows = $this->getDataRowsUniversal($reader, $fullPathToFile, $this->startFromRow, $this->endColumn);

        if (!$rows) {
            return null;
        }
        return $rows;
    }

    public function hydrate($rows)
    {
        $result = [];
        $item = [];
        $start = false;
        $this->currentItem = null;
        $erAlreadyChecked = false;
        $ppeAlreadyChecked = false;

        foreach ($rows as $key => $row) {
            $title = $row[0];
            $value = $row[1];

            // starts
            if ($title == 'PPE') {
                $start = true;
            }

            if (!$start) {
                continue;
            }

            /** @var EnergyData $energyData */
            $energyData = $this->getCurrentItem();
            if ($title == 'PPE') {
                if ($ppeAlreadyChecked) { // PPE starts new item, it means old one is ready and must be added
                    $this->updateCurrentItemConsumption();
                    $currentItem = $this->getCurrentItem();

                    $result[] = $currentItem;

                    $this->currentItem = null; // reset
                    $erAlreadyChecked = false;
                    $energyData = $this->getCurrentItem(); // make new item
                }
                $energyData->setPpCode($value);
                $ppeAlreadyChecked = true;
            } elseif ($title == 'T') {
                $energyData->setTariff($value);
            } elseif ($title == 'DCPR') {
                $energyData->setBillingPeriodFrom(\DateTime::createFromFormat($this->dateFormat, $value));
            } elseif ($title == 'DCKR') {
                $energyData->setBillingPeriodTo(\DateTime::createFromFormat($this->dateFormat, $value));
                if ($energyData->getBillingPeriodTo() === false) {
                    throw new \RuntimeException('Błędny format daty w pliku: ' . $this->getFilename());
                }
            } elseif ($title == 'NL') {
                $energyData->setDeviceId($value);
            } elseif ($title == 'ER') {
                if ($erAlreadyChecked) {
                    $energyData->setConsumptionKwh($value);
                }
                $erAlreadyChecked = true;
            } elseif ($title == 'ZR') {
                $energyData->setArea($value);
                $energyData->setAreaOriginal($value);
            } elseif ($title == 'SPR') {
                $energyData->setStateStart($value);
            } elseif ($title == 'SKR') {
                $energyData->setStateEnd($value);
            } elseif ($title == 'TSKR') {
                if ($value == 'ZAL' || $value == 'ZDJ' || $value == 'RZECZ' || $value == 'ROZ') {
                    $readingType = self::TYPE_READING_REAL;
                } else {
                    $readingType = self::TYPE_READING_ESTIMATE;
                }
                $energyData->setReadingType($readingType);
                $energyData->setReadingTypeOriginal($value);
            } elseif ($title == 'MR') {
                $energyData->setRatio($value);
            }
        }

        // last one need to be added here
        $this->updateCurrentItemConsumption();
        $result[] = $this->getCurrentItem();

        return $result;
    }

    private function getCurrentItem()
    {
        if (!$this->currentItem) {
            $this->currentItem = new EnergyData();
            $this->currentItem->setEnergyType(self::TYPE_ENERGY_CODE);
            $this->currentItem->setFilename($this->getFilename());
            $this->currentItem->setCode($this->code);
            $this->currentItem->setStateStart(0);
        }
        return $this->currentItem;
    }

    private function updateCurrentItemConsumption()
    {
        if (!$this->currentItem) {
            return;
        }

        if (!$this->currentItem->getConsumptionKwh()) {
            $this->currentItem->setConsumptionKwh($this->currentItem->getStateEnd() - $this->currentItem->getStateStart());
        }
    }

    public function validate($objects)
    {
        return [];
    }
}