<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;

class SellerModel implements OptionArrayInterface
{
    const OPTION_ENERGY = 1;
    const OPTION_GAS = 2;

    const ENTITY = 'GCRMCRMBundle:Seller';

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public static function getOptionArray()
    {
        return [
            self::OPTION_ENERGY => 'Prąd',
            self::OPTION_GAS => 'Gaz',
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

    public function getRecordByValue($value)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['option' => $value]);
    }
}