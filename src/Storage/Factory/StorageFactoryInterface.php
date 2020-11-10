<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\StorageInterface;

interface StorageFactoryInterface
{
    /**
     * @param array<mixed> $parameters
     */
    public function createService(array $parameters): ?StorageInterface;

    public function getStorageType(): string;
}
