<?php

namespace GCRM\CRMBundle\Service;

class PaymentImporterModel implements OptionArrayInterface
{
    const BANK_TYPE_SANTANDER = 1;
    const BANK_TYPE_PEKAO = 2;
    const BANK_TYPE_ING = 3;
    const BANK_TYPE_SANTANDER_V2 = 4;

    public static function getOptionArray()
    {
        return [
            self::BANK_TYPE_ING => 'ING',
            self::BANK_TYPE_PEKAO => 'PEKAO',
//            self::BANK_TYPE_SANTANDER => 'Santander',
            self::BANK_TYPE_SANTANDER_V2 => 'Santander',
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

    static public function checkIfValidFileBankType($id)
    {
        $bankTypes = [
            self::BANK_TYPE_SANTANDER => 'Santander',
            self::BANK_TYPE_PEKAO => 'PEKAO',
            self::BANK_TYPE_ING => 'ING',
            self::BANK_TYPE_SANTANDER_V2 => 'Santander',
        ];

        if (isset($bankTypes[$id])) {
            return true;
        }
        return false;
    }
}