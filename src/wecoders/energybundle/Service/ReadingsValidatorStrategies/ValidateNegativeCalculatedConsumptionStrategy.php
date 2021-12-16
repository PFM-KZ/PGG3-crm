<?php

namespace Wecoders\EnergyBundle\Service\ReadingsValidatorStrategies;

use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\ReadingsValidationException;

class ValidateNegativeCalculatedConsumptionStrategy implements ReadingsValidatorStrategyInterface
{
    const ERROR_MESSAGE = '(#%s) - Negative calculated consumption';

    public function validate($records)
    {
        /** @var EnergyData $record */
        foreach ($records as $record) {
            if ($record->getCalculatedConsumptionKwh() < 0) {
                throw new ReadingsValidationException(sprintf(self::ERROR_MESSAGE, $record->getId()));
            }
        }
    }

}