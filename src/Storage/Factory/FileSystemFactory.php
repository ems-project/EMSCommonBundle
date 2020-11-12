<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\FileSystemStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class FileSystemFactory implements StorageFactoryInterface
{
    /** @var string */
    const STORAGE_TYPE = 'fs';
    /** @var string */
    const STORAGE_CONFIG_PATH = 'path';
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /** @var string[]  */
    private $usedFolder = [];

    /**
     * @param array<mixed> $parameters
     * @return StorageInterface
     */
    public function createService(array $parameters): ?StorageInterface
    {
        if (self::STORAGE_TYPE !== $parameters[StorageFactoryInterface::STORAGE_CONFIG_TYPE] ?? null) {
            throw new \RuntimeException(sprintf('The storage service type doesn\'t match \'%s\'', self::STORAGE_TYPE));
        }

        $path = $parameters[self::STORAGE_CONFIG_PATH] ?? null;
        if (!\is_string($path)) {
            throw new \RuntimeException('An url parameter is mandatory to instantiate a FileSystemStorage');
        }

        if ($path === '') {
            return null;
        }

        $realPath = \realpath($path);
        if ($realPath === false) {
            throw new \RuntimeException('The url parameter can\'t be converted into a real path');
        }

        if (\in_array($realPath, $this->usedFolder)) {
            $this->logger->warning(sprintf('The folder %s is already used by another storage service', $realPath));
            return null;
        }

        $this->usedFolder[] = $realPath;
        return new FileSystemStorage($realPath);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }

    /**
     * @return array<mixed>
     */
    public static function getDefaultParameters(): array
    {
        return [
            self::STORAGE_CONFIG_TYPE => self::STORAGE_TYPE,
            self::STORAGE_CONFIG_PATH => null,
        ];
    }
}
