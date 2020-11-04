<?php

namespace EMS\CommonBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class EMSCommonExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('twig.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader->load('storage.xml');

        if ($config['profiler']) {
            $loader->load('profiler.xml');
        }

        $container->setParameter('ems_common.hash_algo', $config['hash_algo']);
        $container->setParameter('ems_common.storage_path', $config['storage_path']);
        $container->setParameter('ems_common.backend_url', $config['backend_url']);
        $container->setParameter('ems_common.s3_credentials', $config['s3_credentials']);
        $container->setParameter('ems_common.s3_bucket', $config['s3_bucket']);
        $container->setParameter('ems_common.elasticsearch_hosts', $config['elasticsearch_hosts']);
    }
}
