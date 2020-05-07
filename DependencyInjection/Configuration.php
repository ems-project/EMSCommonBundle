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
                ->scalarNode('hash_algo')->defaultValue('sha1')->end()
                ->scalarNode('storage_path')->defaultValue(null)->end()
                ->scalarNode('backend_url')->defaultValue(null)->end()
                ->scalarNode('s3_bucket')->defaultValue(null)->end()
                ->variableNode('s3_credentials')->defaultValue([])->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
