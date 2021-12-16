<?php

namespace AppBundle\Twig;

use AppBundle\Service\ApplicationVersion;
use Twig_Extension;

class AppVersion extends Twig_Extension
{
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'appVersion',
                array($this, 'appVersion')
            ),
        );
    }

    public function appVersion(){
        return ApplicationVersion::get();
    }
}