<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\HttpStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class HttpFactory extends AbstractFactory implements StorageFactoryInterface
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

        if (null === $baseUrl || '' === $baseUrl) {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);

            return null;
        }

        $usage = null === $authKey ? StorageInterface::STORAGE_USAGE_EXTERNAL : $config[self::STORAGE_CONFIG_USAGE];

        return new HttpStorage($this->logger, $baseUrl, $getUrl, $usage, $authKey);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{type: string, base-url: null|string, get-url: string, auth-key: null|string}
     * @return array{type: string, base-url: null|string, get-url: string, auth-key: null|string, usage: int}
>>>>>>> ems/develop
     */
    private function resolveParameters(array $parameters): array
    {
        $resolver = $this->getDefaultOptionsResolver();
        $resolver
            ->setDefaults([
                self::STORAGE_CONFIG_TYPE => self::STORAGE_TYPE,
                self::STORAGE_CONFIG_BASE_URL => null,
                self::STORAGE_CONFIG_GET_URL => '/public/file/',
                self::STORAGE_CONFIG_AUTH_KEY => null,
                self::STORAGE_CONFIG_USAGE => StorageInterface::STORAGE_USAGE_BACKUP_ATTRIBUTE,
            ])
            ->setRequired(self::STORAGE_CONFIG_GET_URL)
            ->setAllowedTypes(self::STORAGE_CONFIG_BASE_URL, ['null', 'string'])
            ->setAllowedTypes(self::STORAGE_CONFIG_GET_URL, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_AUTH_KEY, ['null', 'string'])
            ->setAllowedValues(self::STORAGE_CONFIG_TYPE, [self::STORAGE_TYPE])
        ;

        /** @var array{type: string, base-url: null|string, get-url: string, auth-key: null|string, usage: int} $resolvedParameter */
        $resolvedParameter = $resolver->resolve($parameters);

        return $resolvedParameter;
    }
}
