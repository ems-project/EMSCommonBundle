<?php

namespace EMS\CommonBundle;

use EMS\CommonBundle\DependencyInjection\Compiler\StorageFactoryCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EMSCommonBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new StorageFactoryCompilerPass(), PassConfig::TYPE_OPTIMIZE);
    }
}
