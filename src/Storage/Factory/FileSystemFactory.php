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

        $path = $config[self::STORAGE_CONFIG_PATH] ?? null;
        if (!\is_string($path)) {
            throw new \RuntimeException('Unexpected path type');
        }

        if ($path === '') {
            return null;
        }

        $realPath = \realpath($path);
        if ($realPath === false) {
            throw new \RuntimeException('The url parameter can\'t be converted into a real path');
        }

        if (\in_array($realPath, $this->usedFolder)) {
            $this->logger->warning(sprintf('The folder %s is already used by another storage service', $realPath));
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
     * @return array<string, mixed>
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
        ;

        return $resolver->resolve($parameters);
    }
}
