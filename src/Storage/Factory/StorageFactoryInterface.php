<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\StorageInterface;

interface StorageFactoryInterface
{
    const STORAGE_CONFIG_TYPE = 'type';
    /**
     * @param array<mixed> $parameters
     */
    public function createService(array $parameters): ?StorageInterface;

    public function getStorageType(): string;

    /**
     * @return array<mixed>
     */
    public static function getDefaultParameters(): array;
}
