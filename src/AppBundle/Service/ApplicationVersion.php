<?php

namespace AppBundle\Service;

class ApplicationVersion
{
    public static function get()
    {
        return trim(exec('git describe --tags --abbrev=0'));
    }
}