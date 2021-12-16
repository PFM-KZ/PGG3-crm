<?php

namespace GCRM\CRMBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use GCRM\CRMBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;

class GCRMCRMBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideServiceCompilerPass());
    }
}
