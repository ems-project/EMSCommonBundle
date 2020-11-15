<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\SftpStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

    /**
     * @param array<string, mixed> $parameters
     */
    public function createService(array $parameters): ?StorageInterface
    {
        $config = $this->resolveParameters($parameters);

        $host = $config[self::STORAGE_CONFIG_HOST] ?? null;
        if ($host === null || $host === '') {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
        }

        $path = $config[self::STORAGE_CONFIG_PATH] ?? null;
        if (!\is_string($path)) {
            throw new \RuntimeException('Unexpected path type');
        }
        $username = $config[self::STORAGE_CONFIG_USERNAME] ?? null;
        if (!\is_string($username)) {
            throw new \RuntimeException('Unexpected username type');
        }
        $publicKeyFile = $config[self::STORAGE_CONFIG_PUBLIC_KEY_FILE] ?? null;
        if (!\is_string($publicKeyFile)) {
            throw new \RuntimeException('Unexpected public key file type');
        }
        $privateKeyFile = $config[self::STORAGE_CONFIG_PRIVATE_KEY_FILE] ?? null;
        if (!\is_string($privateKeyFile)) {
            throw new \RuntimeException('Unexpected private key file type');
        }
        $passwordPhrase = $config[self::STORAGE_CONFIG_PASSWORD_PHRASE] ?? null;
        if ($passwordPhrase !== null && !\is_string($passwordPhrase)) {
            throw new \RuntimeException('Unexpected password phrase type');
        }
        $port = \intval($config[self::STORAGE_CONFIG_PORT] ?? 22);

        return new SftpStorage($host, $path, $username, $publicKeyFile, $privateKeyFile, false, $passwordPhrase, $port);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    private function resolveParameters(array $parameters): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                self::STORAGE_CONFIG_TYPE => self::STORAGE_TYPE,
                self::STORAGE_CONFIG_HOST => null,
                self::STORAGE_CONFIG_PATH => null,
                self::STORAGE_CONFIG_USERNAME => null,
                self::STORAGE_CONFIG_PUBLIC_KEY_FILE => null,
                self::STORAGE_CONFIG_PRIVATE_KEY_FILE => null,
                self::STORAGE_CONFIG_PASSWORD_PHRASE => null,
                self::STORAGE_CONFIG_PORT => 22,
            ])
            ->setAllowedValues(self::STORAGE_CONFIG_TYPE, [self::STORAGE_TYPE])
        ;

        return $resolver->resolve($parameters);
    }
}
