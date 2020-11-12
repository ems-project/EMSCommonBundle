<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\S3Storage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class S3Factory implements StorageFactoryInterface
{
    /** @var string */
    const STORAGE_TYPE = 's3';
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createService(array $parameters): ?StorageInterface
    {
        if (self::STORAGE_TYPE !== $parameters[StorageFactoryInterface::STORAGE_TYPE_FIELD] ?? null) {
            throw new \RuntimeException(sprintf('The storage service type doesn\'t match \'%s\'', self::STORAGE_TYPE));
        }

        $credentials = $parameters['credentials'] ?? null;
        $bucket = $parameters['bucket'] ?? null;

        if ($credentials === null || $bucket === null) {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
            return null;
        }

        if (!\is_string($bucket)) {
            throw new \RuntimeException('Unexpected bucket');
        }

        if (!\is_array($credentials)) {
            throw new \RuntimeException('Unexpected credentials');
        }

        return new S3Storage($credentials, $bucket);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }
}
