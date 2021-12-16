<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;

class EnergyTypeModel
{
    const TYPE_ENERGY = 1;
    const TYPE_GAS = 2;

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    static public function getOptionArray()
    {
        return [
            self::TYPE_ENERGY => 'PRĄD',
            self::TYPE_GAS => 'GAZ',
        ];
    }

    static public function getOptionByValue($value)
    {
        $options = self::getOptionArray();
        if ($options) {
            foreach ($options as $key => $value) {
                if ($key == $value) {
                    return $value;
                }
            }
        }

        return null;
    }
}