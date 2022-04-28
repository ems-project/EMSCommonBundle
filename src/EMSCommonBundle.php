<?php

namespace EMS\CommonBundle;

use EMS\CommonBundle\DependencyInjection\Compiler\MetricPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EMSCommonBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }
}
