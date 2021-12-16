<?php

namespace GCRM\CRMBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('easyadmin.configuration.menu_config_pass');
        $definition->setClass('GCRM\CRMBundle\Configuration\MenuConfigPass');
        $definition->setAutowired(true);

        $definition = $container->getDefinition('easyadmin.config.manager');
        $definition->setClass('GCRM\CRMBundle\Configuration\ConfigManager');
        $definition->setAutowired(true);
    }
}