<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\StorageInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractFactory implements StorageFactoryInterface
{
    protected function getDefaultOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                self::STORAGE_CONFIG_TYPE => null,
                self::STORAGE_CONFIG_USAGE => StorageInterface::STORAGE_USAGE_CACHE_ATTRIBUTE,
            ])
            ->setAllowedTypes(self::STORAGE_CONFIG_TYPE, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_USAGE, 'string')
            ->setRequired(self::STORAGE_CONFIG_TYPE)
            ->setAllowedValues(self::STORAGE_CONFIG_USAGE, \array_keys(StorageInterface::STORAGE_USAGES))
            ->setNormalizer(self::STORAGE_CONFIG_USAGE, self::usageResolver())
        ;
        return $resolver;
    }

    protected function usageResolver(): \Closure
    {
        return function (Options $options, string $value): int {
            if (isset(StorageInterface::STORAGE_USAGES[$value])) {
                return StorageInterface::STORAGE_USAGES[$value];
            }
            throw new \RuntimeException(\sprintf('Unsupported storage usage value %s', $value));
        };
    }
}
