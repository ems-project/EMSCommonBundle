<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\SftpStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class SftpFactory implements StorageFactoryInterface
{
    /** @var string */
    const STORAGE_TYPE = 'sftp';
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createService(array $parameters): ?StorageInterface
    {
        if (self::STORAGE_TYPE !== $parameters['type'] ?? null) {
            throw new \RuntimeException(sprintf('The storage service type doesn\'t match \'%s\'', self::STORAGE_TYPE));
        }

        $host = $parameters['host'] ?? null;
        if ($host === null || $host === '') {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
        }

        $path = $parameters['path'] ?? null;
        if (!\is_string($path)) {
            throw new \RuntimeException('Unexpected path');
        }
        $username = $parameters['username'] ?? null;
        if (!\is_string($username)) {
            throw new \RuntimeException('Unexpected username');
        }
        $publicKeyFile = $parameters['public-key-file'] ?? null;
        if (!\is_string($publicKeyFile)) {
            throw new \RuntimeException('Unexpected public key file');
        }
        $privateKeyFile = $parameters['private-key-file'] ?? null;
        if (!\is_string($privateKeyFile)) {
            throw new \RuntimeException('Unexpected $private key file');
        }
        $passwordPhrase = $parameters['password-phrase'] ?? null;
        if ($passwordPhrase !== null && !\is_string($passwordPhrase)) {
            throw new \RuntimeException('Unexpected password phrase');
        }
        $port = \intval($parameters['port'] ?? 22);

        return new SftpStorage($host, $path, $username, $publicKeyFile, $privateKeyFile, false, $passwordPhrase, $port);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }
}
