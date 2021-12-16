<?php

namespace GCRM\CRMBundle\Twig;

use Doctrine\ORM\EntityManager;
use Twig_Extension;

class OptionArray extends Twig_Extension
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('getOptionByValue', array($this, 'getOptionByValue')),
        );
    }

    public function getOptionByValue($value, $class, $method = 'getOptionByValue')
    {
        return $class::$method($value);
    }
}