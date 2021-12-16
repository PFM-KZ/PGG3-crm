<?php

namespace GCRM\CRMBundle\Service;

class TransactionProcedure implements OptionArrayInterface
{
    const SW = 1;
    const EE = 2;
    const TP = 3;
    const TT_WNT = 4;
    const TT_D = 5;
    const MR_T = 6;
    const MR_UZ = 7;
    const I_42 = 8;
    const I_63 = 9;
    const B_SPV = 10;
    const B_SPV_DOSTAWA = 11;
    const B_MPV_PROWIZJA = 12;
    const MPP = 13;

    public static function getOptionArray()
    {
        return [
            self::SW => 'SW',
            self::EE => 'EE',
            self::TP => 'TP',
            self::TT_WNT => 'TT_WNT',
            self::TT_D => 'TT_D',
            self::MR_T => 'MR_T',
            self::MR_UZ => 'MR_UZ',
            self::I_42 => 'I_42',
            self::I_63 => 'I_63',
            self::B_SPV => 'B_SPV',
            self::B_SPV_DOSTAWA => 'B_SPV_DOSTAWA',
            self::B_MPV_PROWIZJA => 'B_MPV_PROWIZJA',
            self::MPP => 'MPP',
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
}