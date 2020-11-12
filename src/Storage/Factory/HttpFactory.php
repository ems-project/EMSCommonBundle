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

        $baseUrl = $config[self::STORAGE_CONFIG_BASE_URL] ?? null;
        $getUrl = $config[self::STORAGE_CONFIG_GET_URL] ?? null;
        $authKey = $config[self::STORAGE_CONFIG_AUTH_KEY] ?? null;

        if (!\is_string($baseUrl)) {
            throw new \RuntimeException('Unexpected base url type');
        }

        if (!\is_string($getUrl)) {
            throw new \RuntimeException('Unexpected get url type');
        }

        if ($authKey !== null && !\is_string($authKey)) {
            throw new \RuntimeException('Unexpected authentication key type');
        }

        if ($baseUrl === '') {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
            return null;
        }

        return new HttpStorage($baseUrl, $getUrl, $authKey);
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
                self::STORAGE_CONFIG_BASE_URL => null,
                self::STORAGE_CONFIG_GET_URL => '/public/file/',
                self::STORAGE_CONFIG_AUTH_KEY => null,
            ])
            ->setAllowedValues(self::STORAGE_CONFIG_TYPE, [self::STORAGE_TYPE])
        ;

        return $resolver->resolve($parameters);
    }
}
