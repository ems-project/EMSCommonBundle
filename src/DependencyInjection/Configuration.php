<?php

declare(strict_types=1);

namespace EMS\CommonBundle\DependencyInjection;

use EMS\CommonBundle\Common\Metric\CollectorRegistryFactory;
use EMS\CommonBundle\Common\Standard\Json;
use Monolog\Logger;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private const ELASTICSEARCH_DEFAULT_HOSTS = ['http://localhost:9200'];
    private const LOG_LEVEL = Logger::NOTICE;

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ems_common');
        /* @var $rootNode ArrayNodeDefinition */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->variableNode('storages')->defaultValue([])->end()
                ->booleanNode('profiler')->defaultFalse()->end()
                ->scalarNode('hash_algo')->defaultValue('sha1')->end()
                ->scalarNode('backend_url')->defaultValue(null)->end()
                ->scalarNode('backend_api_key')->defaultValue(null)->end()
                ->variableNode('elasticsearch_hosts')->defaultValue(self::ELASTICSEARCH_DEFAULT_HOSTS)->end()
                ->integerNode('log_level')->defaultValue(self::LOG_LEVEL)->end()
                ->arrayNode('redis')
                    ->children()
                        ->scalarNode('host')->end()
                        ->integerNode('port')->end()
                        ->scalarNode('prefix')->end()
                    ->end()
                ->end()
            ->end()
        ;

        $this->addMetricSection($rootNode);

        return $treeBuilder;
    }

    private function addMetricSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('metric')
                    ->children()
                        ->booleanNode('enabled')
                            ->defaultFalse()
                        ->end()
                        ->scalarNode('type')
                            ->defaultValue(CollectorRegistryFactory::TYPE_IN_MEMORY)
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
            ->end();
        ;
    }
}
