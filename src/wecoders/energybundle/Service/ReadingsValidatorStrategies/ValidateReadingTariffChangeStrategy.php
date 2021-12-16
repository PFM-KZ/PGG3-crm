<?php

namespace Wecoders\EnergyBundle\Service\ReadingsValidatorStrategies;

use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\ReadingsValidationException;

class ValidateReadingTariffChangeStrategy implements ReadingsValidatorStrategyInterface
{
    const ERROR_MESSAGE = '(#%s) - Reading tariff change';

    public function validate($records)
    {
        $tariffs = [];
        /** @var EnergyData $record */
        $added = false;
        foreach ($records as $record) {
            // add only on first iteration
            if (!$added) {
                $tariffs[] = $record->getTariff();
                $added = true;
                continue;
            }

            // checks if tariff is still the same
            if (!in_array($record->getTariff(), $tariffs)) {
                throw new ReadingsValidationException(sprintf(self::ERROR_MESSAGE, $record->getId()));
            }
        }
    }

}