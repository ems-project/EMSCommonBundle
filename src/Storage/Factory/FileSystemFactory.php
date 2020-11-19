<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Factory;

use EMS\CommonBundle\Storage\Service\FileSystemStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileSystemFactory implements StorageFactoryInterface
{
    /** @var string */
    const STORAGE_TYPE = 'fs';
    /** @var string */
    const STORAGE_CONFIG_PATH = 'path';
    /** @var LoggerInterface */
    private $logger;
    /** @var string[] */
    private $usedFolder = [];
    /** @var string */
    private $projectDir;

    public function __construct(LoggerInterface $logger, string $projectDir)
    {
        $this->logger = $logger;
        $this->projectDir = $projectDir;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function createService(array $parameters): ?StorageInterface
    {
        $config = $this->resolveParameters($parameters);

        $path = $config[self::STORAGE_CONFIG_PATH] ?? null;

        if ('' === $path) {
            @\trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);

            return null;
        }

        if (('.') === \substr($path, 0, 1)) {
            $path = $this->projectDir.DIRECTORY_SEPARATOR.$path;
        }

        $realPath = \realpath($path);
        if (false === $realPath) {
            \mkdir($path, 0777, true);
        }

        $realPath = \realpath($path);
        if (false === $realPath) {
            throw new \RuntimeException('The path parameter can\'t be converted into a real path');
        }

        if (\in_array($realPath, $this->usedFolder)) {
            $this->logger->warning(\sprintf('The folder %s is already used by another storage service', $realPath));

            return null;
        }

        $this->usedFolder[] = $realPath;

        return new FileSystemStorage($realPath);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return array{type: string, path: string}
     */
    private function resolveParameters(array $parameters): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                self::STORAGE_CONFIG_TYPE => self::STORAGE_TYPE,
                self::STORAGE_CONFIG_PATH => null,
            ])
            ->setAllowedValues(self::STORAGE_CONFIG_TYPE, [self::STORAGE_TYPE])
            ->setRequired(self::STORAGE_CONFIG_TYPE)
            ->setRequired(self::STORAGE_CONFIG_PATH)
            ->setAllowedTypes(self::STORAGE_CONFIG_TYPE, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_PATH, 'string')
        ;

        /** @var array{type: string, path: string} $resolvedParameter */
        $resolvedParameter = $resolver->resolve($parameters);

        return $resolvedParameter;
    }
}
