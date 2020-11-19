<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\StorageInterface;

interface StorageFactoryInterface
{
    public const STORAGE_CONFIG_TYPE = 'type';

    /**
     * @param array<string, mixed> $parameters
     */
    public function createService(array $parameters): ?StorageInterface;

    public function getStorageType(): string;
}
