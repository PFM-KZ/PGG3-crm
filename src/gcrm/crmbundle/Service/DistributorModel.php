<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Distributor;
use Wecoders\EnergyBundle\Service\TariffModel;

class DistributorModel
{
    const ENTITY = 'GCRMCRMBundle:Distributor';

    const DISTRIBUTOR_INNOGY = 1;
    const DISTRIBUTOR_ENEA = 2;
    const DISTRIBUTOR_PGE = 3;
    const DISTRIBUTOR_TAURON = 4;
    const DISTRIBUTOR_ENERGA = 5;

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecord($id)
    {
        return $this->em->getRepository(self::ENTITY)->find($id);
    }

    public function filterEnergyPricesByDistributorTableData($energyPrices, $tariffCode, $distributor)
    {
        if (!$energyPrices || (is_array($energyPrices) && !count($energyPrices))) {
            return $energyPrices;
        }

        // no distributor defined, return not modified data
        if (!is_object($distributor) || !($distributor instanceof Distributor)) {
            return $energyPrices;
        }

        // if tariff is not defined, return not modified data
        if (!$tariffCode) {
            return $energyPrices;
        }

        $tariffCode = mb_strtolower($tariffCode);
        $isDayAndNight = null;

        $dayTypeCodes = [TariffModel::TARIFF_ZONE_DAY, TariffModel::TARIFF_ZONE_NIGHT];
        $peakTypeCodes = [TariffModel::TARIFF_ZONE_PEAK, TariffModel::TARIFF_ZONE_OFF_PEAK];

        if ($distributor->getId() == DistributorModel::DISTRIBUTOR_INNOGY) {
            if (in_array($tariffCode, ['g12', 'g12w', 'g12as'])) { // day tariffs
                return $this->filterEnergyPricesFromCodes($energyPrices, $peakTypeCodes);
            }
        } elseif ($distributor->getId() == DistributorModel::DISTRIBUTOR_ENEA) {
            if (in_array($tariffCode, ['g12', 'g12p'])) { // day tariffs
                return $this->filterEnergyPricesFromCodes($energyPrices, $peakTypeCodes);
            }
            if (in_array($tariffCode, ['g12w'])) { // peak tariffs
                return $this->filterEnergyPricesFromCodes($energyPrices, $dayTypeCodes);
            }
        } elseif ($distributor->getId() == DistributorModel::DISTRIBUTOR_PGE) {
            if (in_array($tariffCode, ['g12', 'g12as', 'g12n', 'g12w'])) { // day tariffs
                return $this->filterEnergyPricesFromCodes($energyPrices, $peakTypeCodes);
            }
        } elseif ($distributor->getId() == DistributorModel::DISTRIBUTOR_TAURON) {
            if (in_array($tariffCode, ['g12, g12as'])) { // day tariffs
                return $this->filterEnergyPricesFromCodes($energyPrices, $peakTypeCodes);
            }
            if (in_array($tariffCode, ['g12w'])) { // peak tariffs
                return $this->filterEnergyPricesFromCodes($energyPrices, $dayTypeCodes);
            }
        } elseif ($distributor->getId() == DistributorModel::DISTRIBUTOR_ENERGA) {
            if (in_array($tariffCode, ['g12', 'g12w', 'g12as'])) { // day tariffs
                return $this->filterEnergyPricesFromCodes($energyPrices, $peakTypeCodes);
            }
            if (in_array($tariffCode, ['g12r'])) { // peak tariffs
                return $this->filterEnergyPricesFromCodes($energyPrices, $dayTypeCodes);
            }
        }

        return $energyPrices;
    }

    private function filterEnergyPricesFromCodes($energyPrices, $codes)
    {
        $newPrices = [];
        // remove orher energy prices that stand for
        foreach ($energyPrices as $energyPrice) {
            if (in_array($energyPrice['typeCode'], $codes)) {
                continue;
            }

            $newPrices[] = $energyPrice;
        }

        return $newPrices;
    }

}