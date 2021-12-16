<?php

namespace Wecoders\InvoiceBundle\Service;

class Helper
{
    public function cutRestOfNumber($num, $precision = 2)
    {
        $num = number_format($num, 9, '.', '');

        $pos = mb_strpos($num, '.');
        if ($pos) {
            $int = mb_substr($num, 0, $pos);
            $num = mb_substr($num, $pos + 1, $precision);
            $endValue = $int . '.' . $num;
        } else {
            $endValue = $num;
        }

        return $endValue;
    }

    public function removeFileVersionParameterFromPath($path) {
        $pos = strrpos($path, '?');
        if ($pos !== false) {
            $path = mb_substr($path, 0, $pos);
        }

        return $path;
    }

    public function calculateNetValue($grossValue, $vatPercentage, $precision = 2)
    {
        $netValue = 0;

        if ($vatPercentage && $grossValue > 0) {
            $netValue = number_format(($grossValue / ($vatPercentage / 100 + 1)), $precision, '.', '');
        } elseif ($grossValue > 0) {
            $netValue = number_format($grossValue, $precision);
        }

        return $netValue;
    }

    public function calculateVatValue($netValue, $grossValue, $vatPercentage, $precision = 2)
    {
        $vatValue = 0;

        if ($netValue > 0 && $vatPercentage) {
            $vatValue = number_format(($netValue * $vatPercentage / 100), $precision, '.', '');
        } elseif (!$netValue && $grossValue > 0 && $vatPercentage) {
            $vatValue = number_format(($grossValue - $grossValue / ($vatPercentage / 100 + 1)), $precision, '.', '');
        }

        return $vatValue;
    }

    public function calculateGrossValue($netValue, $vatPercentage, $precision = 2)
    {
        $grossValue = 0;

        if ($netValue > 0 && $vatPercentage) {
            $grossValue = number_format(($netValue + $netValue * $vatPercentage / 100), $precision, '.', '');
        } elseif ($netValue > 0) {
            $grossValue = number_format($netValue, $precision, '.', '');
        }

        return $grossValue;
    }
}