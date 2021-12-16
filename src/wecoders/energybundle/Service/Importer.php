<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityEnea;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityEnerga;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityInnogy;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityPGEBialystok;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityPGELodzMiasto;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityPGELodzTeren;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityPGELublin;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityPGERzeszow;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityPGESkarzyskoKamienna;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityPGEWarszawa;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityPGEZamosc;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ElectricityTauron;
use Wecoders\EnergyBundle\Service\ImporterStrategies\GasPSGGdansk;
use Wecoders\EnergyBundle\Service\ImporterStrategies\GasPSGGdanskV2;
use Wecoders\EnergyBundle\Service\ImporterStrategies\GasPSGPoznan;
use Wecoders\EnergyBundle\Service\ImporterStrategies\GasPSGPoznanV2;
use Wecoders\EnergyBundle\Service\ImporterStrategies\GasPSGTarnowV2;
use Wecoders\EnergyBundle\Service\ImporterStrategies\GasPSGWarszawa;
use Wecoders\EnergyBundle\Service\ImporterStrategies\GasPSGWarszawaV2;
use Wecoders\EnergyBundle\Service\ImporterStrategies\GasPSGTarnow;
use Wecoders\EnergyBundle\Service\ImporterStrategies\GasPSGZabrzeV2;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ImporterStrategyInterface;

class Importer
{
    private $em;
    private $energyDataModel;

    public function __construct(EntityManager $em, EnergyDataModel $energyDataModel)
    {
        $this->em = $em;
        $this->energyDataModel = $energyDataModel;
    }

    public function getStrategies()
    {
        return [
            new GasPSGTarnow($this->em, $this->energyDataModel),
            new GasPSGTarnowV2($this->em, $this->energyDataModel),
            new GasPSGGdansk($this->em, $this->energyDataModel),
            new GasPSGGdanskV2($this->em, $this->energyDataModel),
            new GasPSGZabrzeV2($this->em, $this->energyDataModel),
            new GasPSGPoznan($this->em, $this->energyDataModel),
            new GasPSGPoznanV2($this->em, $this->energyDataModel),
            new GasPSGWarszawa($this->em, $this->energyDataModel),
            new GasPSGWarszawaV2($this->em, $this->energyDataModel),
            new ElectricityEnerga($this->em, $this->energyDataModel),
            new ElectricityEnea($this->em, $this->energyDataModel),
            new ElectricityPGEWarszawa($this->em, $this->energyDataModel),
            new ElectricityPGESkarzyskoKamienna($this->em, $this->energyDataModel),
            new ElectricityPGELodzTeren($this->em, $this->energyDataModel),
            new ElectricityPGELodzMiasto($this->em, $this->energyDataModel),
            new ElectricityPGEBialystok($this->em, $this->energyDataModel),
            new ElectricityPGERzeszow($this->em, $this->energyDataModel),
            new ElectricityPGEZamosc($this->em, $this->energyDataModel),
            new ElectricityPGELublin($this->em, $this->energyDataModel),
            new ElectricityInnogy($this->em, $this->energyDataModel),
            new ElectricityTauron($this->em, $this->energyDataModel),
        ];
    }

    public function getOptionArray()
    {
        $result = [];
        $strategies = $this->getStrategies();

        /** @var ImporterStrategyInterface $strategy */
        foreach ($strategies as $strategy) {
            $result[$strategy->getCode()] = $strategy->getValue();
        }
        return $result;
    }

    public function getStrategyByCode($code)
    {
        $strategies = $this->getStrategies();

        /** @var ImporterStrategyInterface $strategy */
        foreach ($strategies as $strategy) {
            if ($code == $strategy->getCode()) {
                return $strategy;
            }
        }
        return null;
    }
}