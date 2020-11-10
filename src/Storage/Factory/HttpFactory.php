<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\HttpStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class HttpFactory implements StorageFactoryInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createService(array $parameters): ?StorageInterface
    {
        if ('http' !== $parameters['type'] ?? null) {
            throw new \RuntimeException('The storage service type doesn\'t match \'http\'');
        }

        $baseUrl = $parameters['base-url'] ?? '';
        $getUrl = $parameters['get-url'] ?? '/public/file/';
        $authKey = $parameters['auth-key'] ?? null;

        if (!\is_string($baseUrl)) {
            throw new \RuntimeException('Unexpected base url');
        }

        if (!\is_string($getUrl)) {
            throw new \RuntimeException('Unexpected get url');
        }

        if ($authKey !== null && !\is_string($authKey)) {
            throw new \RuntimeException('Unexpected authentication key');
        }

        if ($baseUrl === '') {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
            return null;
        }

        return new HttpStorage($baseUrl, $getUrl, $authKey);
    }

    public function getStorageType(): string
    {
        return 'http';
    }
}
