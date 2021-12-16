<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

abstract class BaseTweaker
{
    protected function deleteContract($pattern, array &$dataRow)
    {
        foreach($dataRow as $key => $value) {
            if(preg_match($pattern, $key)) {
                unset($dataRow[$key]);
            }
        }

        $dataRow = array_values($dataRow);
    }
}