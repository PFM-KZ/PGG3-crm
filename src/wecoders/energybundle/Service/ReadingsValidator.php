<?php

namespace Wecoders\EnergyBundle\Service;

use Wecoders\EnergyBundle\Service\ReadingsValidatorStrategies\ReadingsValidatorStrategyInterface;
use Wecoders\EnergyBundle\Service\ReadingsValidatorStrategies\ValidateNegativeCalculatedConsumptionStrategy;
use Wecoders\EnergyBundle\Service\ReadingsValidatorStrategies\ValidateNegativeConsumptionStrategy;
use Wecoders\EnergyBundle\Service\ReadingsValidatorStrategies\ValidateReadingTariffChangeStrategy;

class ReadingsValidator
{
    private $errors = [];

    public function getErrors()
    {
        return $this->errors;
    }

    public function getStrategies()
    {
        return [
            new ValidateNegativeConsumptionStrategy(),
            new ValidateNegativeCalculatedConsumptionStrategy(),
            new ValidateReadingTariffChangeStrategy(),
        ];
    }

    public function execute($records)
    {
        $strategies = $this->getStrategies();

        /** @var ReadingsValidatorStrategyInterface $strategy */
        foreach ($strategies as $strategy) {
            try {
                $strategy->validate($records);
            } catch (ReadingsValidationException $e) {
                $this->errors[] = $e->getMessage();
            }
        }
    }
}