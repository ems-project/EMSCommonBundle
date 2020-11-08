<?php

namespace EMS\CommonBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class StorageFactoryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ems_common.storage.manager')) {
            return;
        }

        $storageManagerDefinition = $container->findDefinition('ems_common.storage.manager');
        if (!$storageManagerDefinition instanceof Definition) {
            throw new \RuntimeException('Unexpected definition class object');
        }

        $factoryDefinitions = $container->findTaggedServiceIds('ems.storage.factory');
        foreach ($factoryDefinitions as $id => $tags) {
            foreach ($tags as $attributes) {
                $alias = $attributes['alias'] ?? null;
                if (!\is_string($alias)) {
                    throw new \RuntimeException('Unexpected or missing tag alias');
                }
                $storageManagerDefinition->addMethodCall(
                    'addStorageFactory',
                    [new Reference($id), $alias]
                );
            }
        }
    }
}
