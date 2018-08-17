<?php

namespace EMS\CommonBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ems_common');

        $rootNode
            ->children()
                ->booleanNode('profiler')->defaultFalse()->end()
                ->booleanNode('storage')->defaultFalse()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
