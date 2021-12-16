<?php

namespace AppBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig_Extension;

class GetSettingExtension extends Twig_Extension
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('getSetting', array($this, 'getSetting')),
        );
    }

    public function getSetting($settings, $key, $fetchValueMethod = null)
    {
        if (!$settings) {
            return null;
        }

        foreach ($settings as $setting) {
            if ($setting->getName() == $key) {
                if ($fetchValueMethod) {
                    return $setting->$fetchValueMethod();
                }
                return $setting;
            }
        }

        return null;
    }
}