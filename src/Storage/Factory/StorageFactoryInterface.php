<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\StorageInterface;

interface StorageFactoryInterface
{
    public const STORAGE_CONFIG_TYPE = 'type';
    public const STORAGE_CONFIG_READ_ONLY = 'read-only';
    public const STORAGE_CONFIG_TO_SKIP = 'to-skip';

    /**
     * @param array<string, mixed> $parameters
     */
    public function createService(array $parameters): ?StorageInterface;

    public function getStorageType(): string;
}
