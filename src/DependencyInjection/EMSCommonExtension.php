<?php

namespace EMS\CommonBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class EMSCommonExtension extends Extension
{
    /**
     * @param array<mixed> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('contracts.xml');
        $loader->load('controllers.xml');
        $loader->load('services.xml');
        $loader->load('commands.xml');
        $loader->load('twig.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader->load('storage.xml');

        if ($config['profiler']) {
            $loader->load('profiler.xml');
        }

        $container->setParameter('ems_common.hash_algo', $config['hash_algo']);
        $container->setParameter('ems_common.backend_url', $config['backend_url']);
        $container->setParameter('ems_common.elasticsearch_hosts', $config['elasticsearch_hosts']);
        $container->setParameter('ems_common.storages', $config['storages']);
        $container->setParameter('ems_common.log_level', $config['log_level']);
    }
}
