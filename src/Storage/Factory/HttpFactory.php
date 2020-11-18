<?php

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\HttpStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HttpFactory implements StorageFactoryInterface
{
    /** @var string */
    const STORAGE_TYPE = 'http';
    /** @var string */
    const STORAGE_CONFIG_BASE_URL = 'base-url';
    /** @var string */
    const STORAGE_CONFIG_GET_URL = 'get-url';
    /** @var string */
    const STORAGE_CONFIG_AUTH_KEY = 'auth-key';
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

        $baseUrl = $config[self::STORAGE_CONFIG_BASE_URL];
        $getUrl = $config[self::STORAGE_CONFIG_GET_URL];
        $authKey = $config[self::STORAGE_CONFIG_AUTH_KEY];

        if ($baseUrl === null || $baseUrl === '') {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
            return null;
        }

        $readOnly = $config[self::STORAGE_CONFIG_READ_ONLY] && $authKey !== null;
        $skip = $config[self::STORAGE_CONFIG_READ_ONLY] && $authKey !== null;

        return new HttpStorage($this->logger, $baseUrl, $getUrl, $readOnly, $skip, $authKey);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }


    /**
     * @param array<string, mixed> $parameters
     * @return array{type: string, base-url: null|string, get-url: string, auth-key: null|string, read-only: bool, skip: bool}
     */
    private function resolveParameters(array $parameters): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                self::STORAGE_CONFIG_TYPE => self::STORAGE_TYPE,
                self::STORAGE_CONFIG_BASE_URL => null,
                self::STORAGE_CONFIG_GET_URL => '/public/file/',
                self::STORAGE_CONFIG_AUTH_KEY => null,
                self::STORAGE_CONFIG_READ_ONLY => false,
                self::STORAGE_CONFIG_SKIP => false,
            ])
            ->setRequired(self::STORAGE_CONFIG_TYPE)
            ->setRequired(self::STORAGE_CONFIG_GET_URL)
            ->setAllowedTypes(self::STORAGE_CONFIG_TYPE, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_BASE_URL, ['null', 'string'])
            ->setAllowedTypes(self::STORAGE_CONFIG_GET_URL, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_AUTH_KEY, ['null', 'string'])
            ->setAllowedValues(self::STORAGE_CONFIG_TYPE, [self::STORAGE_TYPE])
            ->setAllowedValues(self::STORAGE_CONFIG_READ_ONLY, [true, false])
            ->setAllowedValues(self::STORAGE_CONFIG_SKIP, [true, false])
        ;

        /** @var array{type: string, base-url: null|string, get-url: string, auth-key: null|string, read-only: bool, skip: bool} $resolvedParameter */
        $resolvedParameter = $resolver->resolve($parameters);
        return $resolvedParameter;
    }
}
