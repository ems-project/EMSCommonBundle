<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\FileSystemStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class FileSystemFactory implements StorageFactoryInterface
{

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
        if ('fs' !== $parameters['type'] ?? null) {
            throw new \RuntimeException('The storage service type doesn\'t match \'fs\'');
        }

        $path = $parameters['path'] ?? null;
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
}
