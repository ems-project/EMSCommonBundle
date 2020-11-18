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

        $host = $config[self::STORAGE_CONFIG_HOST];
        if ($host === null || $host === '') {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
            return null;
        }
        $path = $config[self::STORAGE_CONFIG_PATH];
        $username = $config[self::STORAGE_CONFIG_USERNAME];
        $publicKeyFile = $config[self::STORAGE_CONFIG_PUBLIC_KEY_FILE];
        $privateKeyFile = $config[self::STORAGE_CONFIG_PRIVATE_KEY_FILE];
        $passwordPhrase = $config[self::STORAGE_CONFIG_PASSWORD_PHRASE];
        $port = \intval($config[self::STORAGE_CONFIG_PORT]);

        return new SftpStorage($this->logger, $host, $path, $username, $publicKeyFile, $privateKeyFile, $config[self::STORAGE_CONFIG_READ_ONLY], $config[self::STORAGE_CONFIG_SKIP], $passwordPhrase, $port);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }

    /**
     * @param array<string, mixed> $parameters
     * @return array{type: string, host: null|string, path: string, username: string, public-key-file: string, public-key-file: string, private-key-file: string, password-phrase: null|string, port: int, read-only: bool, skip: bool}
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
                self::STORAGE_CONFIG_READ_ONLY => false,
                self::STORAGE_CONFIG_SKIP => false,
            ])
            ->setRequired([
                self::STORAGE_CONFIG_TYPE,
                self::STORAGE_CONFIG_PATH,
                self::STORAGE_CONFIG_USERNAME,
                self::STORAGE_CONFIG_PUBLIC_KEY_FILE,
                self::STORAGE_CONFIG_PRIVATE_KEY_FILE,
                self::STORAGE_CONFIG_PORT,
            ])
            ->setAllowedTypes(self::STORAGE_CONFIG_TYPE, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_HOST, ['string', 'null'])
            ->setAllowedTypes(self::STORAGE_CONFIG_PATH, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_USERNAME, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_PUBLIC_KEY_FILE, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_PRIVATE_KEY_FILE, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_PORT, 'int')
            ->setAllowedValues(self::STORAGE_CONFIG_TYPE, [self::STORAGE_TYPE])
            ->setAllowedValues(self::STORAGE_CONFIG_READ_ONLY, [true, false])
            ->setAllowedValues(self::STORAGE_CONFIG_SKIP, [true, false])
        ;

        /** @var array{type: string, host: null|string, path: string, username: string, public-key-file: string, public-key-file: string, private-key-file: string, password-phrase: null|string, port: int, read-only: bool, skip: bool} $resolvedParameter */
        $resolvedParameter = $resolver->resolve($parameters);
        return $resolvedParameter;
    }
}
