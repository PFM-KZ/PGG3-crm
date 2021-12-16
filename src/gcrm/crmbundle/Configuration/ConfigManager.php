<?php

namespace GCRM\CRMBundle\Configuration;;

use EasyCorp\Bundle\EasyAdminBundle\Cache\CacheManager;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Configuration\ConfigManager as BaseClass;

class ConfigManager extends BaseClass
{
    public function __construct(CacheManager $cacheManager, PropertyAccessorInterface $propertyAccessor, array $originalBackendConfig, $debug)
    {
        $cacheManager->delete('processed_config');
        parent::__construct($cacheManager, $propertyAccessor, $originalBackendConfig, $debug);
    }
}