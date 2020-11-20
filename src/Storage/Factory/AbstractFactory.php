<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\StorageInterface;
use Symfony\Component\OptionsResolver\Options;

abstract class AbstractFactory
{
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
