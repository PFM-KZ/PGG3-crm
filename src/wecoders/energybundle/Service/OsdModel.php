<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\OptionArrayInterface;

class OsdModel implements OptionArrayInterface
{
    const OPTION_GAS_PSG_WARSZAWA = 1;
    const OPTION_ELECTRICITY_ENERGA = 2;
    const OPTION_GAS_PSG_GDANSK = 3;
    const OPTION_GAS_PSG_TARNOW = 4;
    const OPTION_GAS_PSG_ZABRZE = 5;
    const OPTION_GAS_PSG_POZNAN = 6;
    const OPTION_GAS_PSG_WROCLAW = 7;
    const OPTION_ELECTRICITY_TAURON = 8;
    const OPTION_ELECTRICITY_PGE_BIALYSTOK = 9;
    const OPTION_ELECTRICITY_PGE_WARSZAWA = 10;
    const OPTION_ELECTRICITY_PGE_LUBLIN = 11;
    const OPTION_ELECTRICITY_PGE_ZAMOSC = 12;
    const OPTION_ELECTRICITY_PGE_RZESZOW = 13;
    const OPTION_ELECTRICITY_PGE_SKARZYSKO_KAMIENNA = 14;
    const OPTION_ELECTRICITY_PGE_LODZ_MIASTO = 15;
    const OPTION_ELECTRICITY_PGE_LODZ_TEREN = 16;
    const OPTION_ELECTRICITY_ENEA = 17;
    const OPTION_ELECTRICITY_INNOGY = 18;
    const OPTION_GAS_PSG_TARNOW_V2 = 19; // new version from 2020
    const OPTION_GAS_PSG_GDANSK_V2 = 20; // new version from 2020
    const OPTION_GAS_PSG_ZABRZE_V2 = 21; // new version from 2020
    const OPTION_GAS_PSG_POZNAN_V2 = 22; // new version from 2020
    const OPTION_GAS_PSG_WARSZAWA_V2 = 23; // new version from 2020

    const ENTITY = 'WecodersEnergyBundle:Osd';

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public static function getOptionArray()
    {
        return [
            self::OPTION_GAS_PSG_WARSZAWA => 'PSG Warszawa',
            self::OPTION_GAS_PSG_WARSZAWA_V2 => 'PSG Warszawa od 2020 (nowy format)',
            self::OPTION_GAS_PSG_GDANSK => 'PSG Gdańsk',
            self::OPTION_GAS_PSG_GDANSK_V2 => 'PSG Gdańsk od 2020 (nowy format)',
            self::OPTION_GAS_PSG_TARNOW => 'PSG Tarnów',
            self::OPTION_GAS_PSG_TARNOW_V2 => 'PSG Tarnów od 2020 (nowy format)',
            self::OPTION_GAS_PSG_ZABRZE => 'PSG Zabrze',
            self::OPTION_GAS_PSG_ZABRZE_V2 => 'PSG Zabrze od 2020 (nowy format)',
            self::OPTION_GAS_PSG_POZNAN => 'PSG Poznań',
            self::OPTION_GAS_PSG_POZNAN_V2 => 'PSG Poznań od 2020 (nowy format)',
            self::OPTION_GAS_PSG_WROCLAW => 'PSG Wrocław',
            self::OPTION_ELECTRICITY_ENEA => 'Enea',
            self::OPTION_ELECTRICITY_ENERGA => 'Energa',
            self::OPTION_ELECTRICITY_TAURON => 'Tauron',
            self::OPTION_ELECTRICITY_INNOGY => 'Innogy',
            self::OPTION_ELECTRICITY_PGE_BIALYSTOK => 'PGE Białystok',
            self::OPTION_ELECTRICITY_PGE_WARSZAWA => 'PGE Warszawa',
            self::OPTION_ELECTRICITY_PGE_LUBLIN => 'PGE Lublin',
            self::OPTION_ELECTRICITY_PGE_ZAMOSC => 'PGE Zamość',
            self::OPTION_ELECTRICITY_PGE_RZESZOW => 'PGE Rzeszów',
            self::OPTION_ELECTRICITY_PGE_SKARZYSKO_KAMIENNA => 'PGE Skarżysko Kamienna',
            self::OPTION_ELECTRICITY_PGE_LODZ_MIASTO => 'PGE Łódź miasto',
            self::OPTION_ELECTRICITY_PGE_LODZ_TEREN => 'PGE Łódź teren',
        ];
    }

    public static function getOptionByValue($value)
    {
        $options = self::getOptionArray();
        foreach ($options as $key => $option) {
            if ($key == $value) {
                return $option;
            }
        }

        return null;
    }

    public function getRecordByValue($value)
    {
        // assign new formats to old osd record (record osd is the same in database, so it can be fetched from old record)
        if ($value == self::OPTION_GAS_PSG_TARNOW_V2) {
            $value = self::OPTION_GAS_PSG_TARNOW;
        } elseif ($value == self::OPTION_GAS_PSG_GDANSK_V2) {
            $value = self::OPTION_GAS_PSG_GDANSK;
        } elseif ($value == self::OPTION_GAS_PSG_ZABRZE_V2) {
            $value = self::OPTION_GAS_PSG_ZABRZE;
        } elseif ($value == self::OPTION_GAS_PSG_POZNAN_V2) {
            $value = self::OPTION_GAS_PSG_POZNAN;
        } elseif ($value == self::OPTION_GAS_PSG_WARSZAWA_V2) {
            $value = self::OPTION_GAS_PSG_WARSZAWA;
        }

        return $this->em->getRepository(self::ENTITY)->findOneBy(['option' => $value]);
    }
}