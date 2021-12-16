<?php

namespace Wecoders\EnergyBundle\Service\ReadingsValidatorStrategies;

use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\ReadingsValidationException;

class ValidateNegativeConsumptionStrategy implements ReadingsValidatorStrategyInterface
{
    const ERROR_MESSAGE = '(#%s) - Negative consumption';

    public function validate($records)
    {
        /** @var EnergyData $record */
        foreach ($records as $record) {
            if ($record->getConsumptionKwh() < 0) {
                throw new ReadingsValidationException(sprintf(self::ERROR_MESSAGE, $record->getId()));
            }
        }
    }

}