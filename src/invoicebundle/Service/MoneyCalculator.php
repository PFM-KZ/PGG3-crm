<?php

namespace Wecoders\InvoiceBundle\Service;

class MoneyCalculator
{
    public function vatValue($netValue, $grossValue, $vatPercentage)
    {
        $vatValue = 0;

        if ($netValue > 0 && $vatPercentage) {
            $vatValue = number_format(($netValue * $vatPercentage / 100), 2, '.', '');
        } elseif (!$netValue && $grossValue > 0 && $vatPercentage) {
            $vatValue = number_format(($grossValue - $grossValue / ($vatPercentage / 100 + 1)), 2, '.', '');
        }

        return $vatValue;
    }

    public function netValue($netValue, $grossValue, $vatPercentage)
    {
        if ($netValue > 0) {
            return $netValue;
        }

        $netValue = 0;

        if ($vatPercentage && $grossValue > 0) {
            $netValue = number_format(($grossValue / ($vatPercentage / 100 + 1)), 2, '.', '');
        } elseif ($grossValue > 0) {
            $netValue = $grossValue;
        }

        return $netValue;
    }

    public function grossValue($netValue, $grossValue, $vatPercentage)
    {
        if ($grossValue > 0) {
            return $grossValue;
        }

        $grossValue = 0;

        if ($netValue > 0 && $vatPercentage) {
            $grossValue = number_format(($netValue + $netValue * $vatPercentage / 100), 2, '.', '');
        } elseif ($netValue > 0) {
            $grossValue = $netValue;
        }

        return $grossValue;
    }
}