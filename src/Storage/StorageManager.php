<?php

namespace EMS\CommonBundle\Storage;

use EMS\CommonBundle\Storage\Factory\StorageFactoryInterface;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Config\FileLocatorInterface;

class StorageManager
{
    /** @var StorageInterface[] */
    private $adapters = [];
    /** @var StorageFactoryInterface[] */
    private $factories = [];

    /** @var FileLocatorInterface */
    private $fileLocator;

    /** @var string */
    private $hashAlgo;
    /** @var array<array{type?: string, url?: string, required?: bool, read-only?: bool}> */
    private $storageConfigs;

    /**
     * @param iterable<StorageFactoryInterface> $factories
     * @param array<array{type?: string, url?: string, required?: bool, read-only?: bool}> $storageConfigs
     */
    public function __construct(FileLocatorInterface $fileLocator, iterable $factories, string $hashAlgo, array $storageConfigs = [])
    {
        foreach ($factories as $factory) {
            if (!$factory instanceof StorageFactoryInterface) {
                throw new \RuntimeException('Unexpected StorageInterface class');
            }
            $this->addStorageFactory($factory);
        }
        $this->fileLocator = $fileLocator;
        $this->hashAlgo = $hashAlgo;
        $this->storageConfigs = $storageConfigs;
        $this->registerServicesFromConfigs();
    }

    private function addStorageFactory(StorageFactoryInterface $factory): void
    {
        $this->factories[$factory->getStorageType()] = $factory;
    }


    private function registerServicesFromConfigs(): void
    {
        foreach ($this->storageConfigs as $storageConfig) {
            $type = $storageConfig['type'] ?? null;
            if ($type === null) {
                continue;
            }
            $factory = $this->factories[$type] ?? null;
            if ($factory === null) {
                continue;
            }
            $storage = $factory->createService($storageConfig);
            if ($storage !== null) {
                $this->addAdapter($storage);
            }
        }
    }


    public function addAdapter(StorageInterface $storageAdapter): StorageManager
    {
        $this->adapters[] = $storageAdapter;
        return $this;
    }

    public function head(string $hash): bool
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->head($hash)) {
                return true;
            }
        }
        return false;
    }

    public function getStream(string $hash): StreamInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->head($hash)) {
                try {
                    return $adapter->read($hash);
                } catch (NotFoundException $e) {
                }
            }
        }
        throw new NotFoundException($hash);
    }

    public function getContents(string $hash): string
    {
        return $this->getStream($hash)->getContents();
    }

    public function getPublicImage(string $name): string
    {
        $file = $this->fileLocator->locate('@EMSCommonBundle/src/Resources/public/images/' . $name);
        if (is_array($file)) {
            return $file[0] ?? '';
        }
        return $file;
    }

    public function getHashAlgo(): string
    {
        return $this->hashAlgo;
    }

    public function saveContents(string $contents, string $filename, string $mimetype, bool $skipShouldSkipServices = true): string
    {
        $hash = hash($this->hashAlgo, $contents);
        $count = 0;

        foreach ($this->adapters as $adapter) {
            if ($adapter->isReadOnly() || ($skipShouldSkipServices && $adapter->shouldSkip())) {
                break;
            }

            if ($adapter->head($hash)) {
                ++$count;
                continue;
            }

            if (!$adapter->initUpload($hash, strlen($contents), $filename, $mimetype)) {
                continue;
            }

            if (!$adapter->addChunk($hash, $contents)) {
                continue;
            }

            if ($adapter->finalizeUpload($hash)) {
                ++$count;
            }
        }

        if ($count === 0) {
            throw new \RuntimeException(sprintf('Impossible to save the asset identified by the hash %s into at least one storage services', $hash));
        }

        return $hash;
    }

    public function computeStringHash(string $string): string
    {
        return \hash($this->hashAlgo, $string);
    }

    public function computeFileHash(string $filename): string
    {
        $hashFile = \hash_file($this->hashAlgo, $filename);
        if ($hashFile === false) {
            throw new NotFoundException($filename);
        }
        return $hashFile;
    }

    public function initUploadFile(string $fileHash, int $fileSize, string $fileName, string $mimeType, bool $skipShouldSkipServices = true): int
    {
        $count = 0;
        foreach ($this->adapters as $adapter) {
            if ($adapter->isReadOnly() || ($skipShouldSkipServices && $adapter->shouldSkip())) {
                break;
            }
            if ($adapter->initUpload($fileHash, $fileSize, $fileName, $mimeType)) {
                ++$count;
            }
        }

        if ($count === 0) {
            throw new \RuntimeException(sprintf('Impossible to initiate the upload of an asset identified by the hash %s into at least one storage services', $fileHash));
        }

        return $count;
    }

    /**
     * @return array<string, bool>
     */
    public function getHealthStatuses(): array
    {
        $statuses = [];
        foreach ($this->adapters as $adapter) {
            $statuses[$adapter->__toString()] = $adapter->health();
        }
        return $statuses;
    }

    public function getSize(string $hash): int
    {
        foreach ($this->adapters as $adapter) {
            try {
                return $adapter->getSize($hash);
            } catch (\Throwable $e) {
            }
        }
        throw new NotFoundException($hash);
    }

    public function getBase64(string $hash): ?string
    {
        foreach ($this->adapters as $adapter) {
            try {
                $stream = $adapter->read($hash);
            } catch (\Throwable $e) {
                continue;
            }
            return \base64_encode($stream->getContents());
        }
        return null;
    }
}
