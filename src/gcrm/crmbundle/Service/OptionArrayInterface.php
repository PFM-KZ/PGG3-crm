<?php

namespace GCRM\CRMBundle\Service;

interface OptionArrayInterface
{
    public static function getOptionArray();
    public static function getOptionByValue($value);
}