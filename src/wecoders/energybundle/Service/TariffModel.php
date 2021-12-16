<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;

class TariffModel
{
    const TARIFF_ZONE_ALL_DAY = 1;
    const TARIFF_ZONE_PEAK = 2;
    const TARIFF_ZONE_OFF_PEAK = 3;
    const TARIFF_ZONE_DAY = 4;
    const TARIFF_ZONE_NIGHT = 5;
    const TARIFF_ZONE_MORNING_PEAK = 6;
    const TARIFF_ZONE_AFTERNOON_PEAK = 7;
    const TARIFF_ZONE_REMAINING_HOURS_OF_DAY = 8;

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    static public function getOptionArray()
    {
        return [
            self::TARIFF_ZONE_ALL_DAY => 'Całodobowa',
            self::TARIFF_ZONE_DAY => 'Dzienna',
            self::TARIFF_ZONE_NIGHT => 'Nocna',
            self::TARIFF_ZONE_PEAK => 'Szczytowa',
            self::TARIFF_ZONE_OFF_PEAK => 'Pozaszczytowa',
            self::TARIFF_ZONE_MORNING_PEAK => 'Szczyt przedpołudniowy',
            self::TARIFF_ZONE_AFTERNOON_PEAK => 'Szczyt popołudniowy',
            self::TARIFF_ZONE_REMAINING_HOURS_OF_DAY => 'Pozostałe godziny doby',
        ];
    }

    static public function getOptionByValue($item)
    {
        $options = self::getOptionArray();
        if ($options) {
            foreach ($options as $key => $value) {
                if ($key == $item) {
                    return $value;
                }
            }
        }

        return null;
    }

    static public function getOptionKeyByValue($item)
    {
        $options = self::getOptionArray();
        if ($options) {
            foreach ($options as $key => $value) {
                if ($value == $item) {
                    return $key;
                }
            }
        }

        return null;
    }
}