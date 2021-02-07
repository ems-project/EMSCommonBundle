<?php

namespace EMS\CommonBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private const ELASTICSEARCH_DEFAULT_HOSTS = ['http://localhost:9200'];

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ems_common');

        $rootNode
            ->children()
                ->variableNode('storages')->defaultValue([])->end()
                ->booleanNode('profiler')->defaultFalse()->end()
                ->scalarNode('hash_algo')->defaultValue('sha1')->end()
                ->variableNode('elasticsearch_hosts')->defaultValue(self::ELASTICSEARCH_DEFAULT_HOSTS)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
