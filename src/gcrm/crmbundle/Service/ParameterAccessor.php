<?php

namespace GCRM\CRMBundle\Service;

use GCRM\CRMBundle\Entity\Client;
use TZiebura\SmsBundle\Interfaces\ParameterAccessorInterface;

// This will be needed later when global variables will be needed
class ParameterAccessor implements ParameterAccessorInterface
{
    public function getParameter($parameter, $entity)
    {
        if($parameter === 'id' && $entity instanceof Client) {
            return $entity->getId();
        }
    }
}