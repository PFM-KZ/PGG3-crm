<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Wecoders\EnergyBundle\Entity\GTUInterface;

class GTU implements OptionArrayInterface
{
    const GTU_01 = 1;
    const GTU_02 = 2;
    const GTU_03 = 3;
    const GTU_04 = 4;
    const GTU_05 = 5;
    const GTU_06 = 6;
    const GTU_07 = 7;
    const GTU_08 = 8;
    const GTU_09 = 9;
    const GTU_10 = 10;
    const GTU_11 = 11;
    const GTU_12 = 12;
    const GTU_13 = 13;

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public static function getOptionArray()
    {
        return [
            self::GTU_01 => 'GTU_01',
            self::GTU_02 => 'GTU_02',
            self::GTU_03 => 'GTU_03',
            self::GTU_04 => 'GTU_04',
            self::GTU_05 => 'GTU_05',
            self::GTU_06 => 'GTU_06',
            self::GTU_07 => 'GTU_07',
            self::GTU_08 => 'GTU_08',
            self::GTU_09 => 'GTU_09',
            self::GTU_10 => 'GTU_10',
            self::GTU_11 => 'GTU_11',
            self::GTU_12 => 'GTU_12',
            self::GTU_13 => 'GTU_13',
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

    public function updateGTU(GTUInterface $objectToUpdate, $data, $flush = true, $fieldData = 'services', $fieldGtu = 'gtu')
    {
        $gtuCodes = [];
        foreach ($data as $item) {
            $services = $item[$fieldData];
            if (!$services) {
                continue;
            }

            foreach ($services as $service) {
                $gtu = isset($service[$fieldGtu]) ? $service[$fieldGtu] : null;
                if (!$gtu) {
                    continue;
                }

                if (in_array($gtu, $gtuCodes)) {
                    continue;
                }

                $gtuCodes[] = $gtu;
            }
        }

        // reset
        for ($i = 1; $i <= 13; $i++) {
            $setMethod = 'setGtu' . $i;
            $objectToUpdate->$setMethod(false);
        }

        // set new
        if (count($gtuCodes)) {
            foreach ($gtuCodes as $gtu) {
                $setMethod = 'setGtu' . $gtu;
                $objectToUpdate->$setMethod(true);
            }
        }

        $this->em->persist($objectToUpdate);
        if ($flush) {
            $this->em->flush($objectToUpdate);
        }
    }
}