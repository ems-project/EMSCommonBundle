<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\SftpStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class SftpFactory implements StorageFactoryInterface
{
    /** @var string */
    const STORAGE_TYPE = 'sftp';
    /** @var string */
    const STORAGE_CONFIG_HOST = 'host';
    /** @var string */
    const STORAGE_CONFIG_PATH = 'path';
    /** @var string */
    const STORAGE_CONFIG_USERNAME = 'username';
    /** @var string */
    const STORAGE_CONFIG_PUBLIC_KEY_FILE = 'public-key-file';
    /** @var string */
    const STORAGE_CONFIG_PRIVATE_KEY_FILE = 'private-key-file';
    /** @var string */
    const STORAGE_CONFIG_PASSWORD_PHRASE = 'password-phrase';
    /** @var string */
    const STORAGE_CONFIG_PORT = 'port';
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createService(array $parameters): ?StorageInterface
    {
        if (self::STORAGE_TYPE !== $parameters[StorageFactoryInterface::STORAGE_CONFIG_TYPE] ?? null) {
            throw new \RuntimeException(sprintf('The storage service type doesn\'t match \'%s\'', self::STORAGE_TYPE));
        }

        $host = $parameters[self::STORAGE_CONFIG_HOST] ?? null;
        if ($host === null || $host === '') {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
        }

        $path = $parameters[self::STORAGE_CONFIG_PATH] ?? null;
        if (!\is_string($path)) {
            throw new \RuntimeException('Unexpected path');
        }
        $username = $parameters[self::STORAGE_CONFIG_USERNAME] ?? null;
        if (!\is_string($username)) {
            throw new \RuntimeException('Unexpected username');
        }
        $publicKeyFile = $parameters[self::STORAGE_CONFIG_PUBLIC_KEY_FILE] ?? null;
        if (!\is_string($publicKeyFile)) {
            throw new \RuntimeException('Unexpected public key file');
        }
        $privateKeyFile = $parameters[self::STORAGE_CONFIG_PRIVATE_KEY_FILE] ?? null;
        if (!\is_string($privateKeyFile)) {
            throw new \RuntimeException('Unexpected $private key file');
        }
        $passwordPhrase = $parameters[self::STORAGE_CONFIG_PASSWORD_PHRASE] ?? null;
        if ($passwordPhrase !== null && !\is_string($passwordPhrase)) {
            throw new \RuntimeException('Unexpected password phrase');
        }
        $port = \intval($parameters[self::STORAGE_CONFIG_PORT] ?? 22);

        return new SftpStorage($host, $path, $username, $publicKeyFile, $privateKeyFile, false, $passwordPhrase, $port);
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
            self::STORAGE_CONFIG_HOST => null,
            self::STORAGE_CONFIG_PATH => null,
            self::STORAGE_CONFIG_USERNAME => null,
            self::STORAGE_CONFIG_PUBLIC_KEY_FILE => null,
            self::STORAGE_CONFIG_PRIVATE_KEY_FILE => null,
            self::STORAGE_CONFIG_PASSWORD_PHRASE => null,
            self::STORAGE_CONFIG_PORT => 22,
        ];
    }
}
