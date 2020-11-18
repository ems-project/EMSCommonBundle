<?php

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
    /** @var string[]  */
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

        if ($path === '') {
            @trigger_error('You should consider to migrate you storage service configuration to the EMS_STORAGES variable', \E_USER_DEPRECATED);
            return null;
        }

        if (\substr($path, 0, 1) === ('.')) {
            $path = $this->projectDir . DIRECTORY_SEPARATOR . $path;
        }

        $realPath = \realpath($path);
        if ($realPath === false) {
            \mkdir($path, 0777, true);
        }

        $realPath = \realpath($path);
        if ($realPath === false) {
            throw new \RuntimeException('The path parameter can\'t be converted into a real path');
        }

        if (\in_array($realPath, $this->usedFolder)) {
            $this->logger->warning(sprintf('The folder %s is already used by another storage service', $realPath));
            return null;
        }

        $this->usedFolder[] = $realPath;
        return new FileSystemStorage($this->logger, $realPath, $config[self::STORAGE_CONFIG_READ_ONLY], $config[self::STORAGE_CONFIG_TO_SKIP]);
    }

    public function getStorageType(): string
    {
        return self::STORAGE_TYPE;
    }


    /**
     * @param array<string, mixed> $parameters
     * @return array{type: string, path: string, read-only: bool, to-skip: bool}
     */
    private function resolveParameters(array $parameters): array
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                self::STORAGE_CONFIG_TYPE => self::STORAGE_TYPE,
                self::STORAGE_CONFIG_PATH => null,
                self::STORAGE_CONFIG_READ_ONLY => false,
                self::STORAGE_CONFIG_TO_SKIP => false,
            ])
            ->setAllowedValues(self::STORAGE_CONFIG_TYPE, [self::STORAGE_TYPE])
            ->setRequired(self::STORAGE_CONFIG_TYPE)
            ->setRequired(self::STORAGE_CONFIG_PATH)
            ->setAllowedTypes(self::STORAGE_CONFIG_TYPE, 'string')
            ->setAllowedTypes(self::STORAGE_CONFIG_PATH, 'string')
            ->setAllowedValues(self::STORAGE_CONFIG_READ_ONLY, [true, false])
            ->setAllowedValues(self::STORAGE_CONFIG_TO_SKIP, [true, false])
        ;

        /** @var array{type: string, path: string, read-only: bool, to-skip: bool} $resolvedParameter */
        $resolvedParameter = $resolver->resolve($parameters);
        return $resolvedParameter;
    }
}
