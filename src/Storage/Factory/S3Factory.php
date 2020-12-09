<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\S3Storage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;

class S3Factory extends AbstractFactory implements StorageFactoryInterface
{
    /** @var string */
    const STORAGE_TYPE = 's3';
    /** @var string */
    const STORAGE_CONFIG_CREDENTIALS = 'credentials';
    /** @var string */
    const STORAGE_CONFIG_BUCKET = 'bucket';
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function createService(array $parameters): ?StorageInterface
    {
        $config = $this->resolveParameters($parameters);

        $credentials = $config[self::STORAGE_CONFIG_CREDENTIALS] ?? null;
        $bucket = $config[self::STORAGE_CONFIG_BUCKET] ?? null;

        if (null === $credentials || 0 === \count($credentials) || null === $bucket || 0 === \strlen($bucket)) {
            @\trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);

            return null;
        }

        return new S3Storage($this->logger, $credentials, $bucket, $config[self::STORAGE_CONFIG_USAGE]);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{type: string, credentials: null|array, bucket: null|string, usage: int}
     */
    private function resolveParameters(array $parameters): array
    {
        $resolver = $this->getDefaultOptionsResolver();
        $resolver
            ->setDefaults([
                self::STORAGE_CONFIG_TYPE => self::STORAGE_TYPE,
                self::STORAGE_CONFIG_CREDENTIALS => null,
                self::STORAGE_CONFIG_BUCKET => null,
            ])
            ->setAllowedTypes(self::STORAGE_CONFIG_CREDENTIALS, ['null', 'array'])
            ->setAllowedTypes(self::STORAGE_CONFIG_BUCKET, ['null', 'string'])
            ->setAllowedValues(self::STORAGE_CONFIG_TYPE, [self::STORAGE_TYPE])
        ;
        /** @var array{type: string, credentials: null|array, bucket: null|string, usage: int} $resolvedParameter */
        $resolvedParameter = $resolver->resolve($parameters);

        return $resolvedParameter;
    }
}
