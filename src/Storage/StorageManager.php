<?php

namespace EMS\CommonBundle\Storage;

use EMS\CommonBundle\Helper\ArrayTool;
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

    public function saveContents(string $contents, string $filename, string $mimetype, int $usageType): string
    {
        $hash = $this->computeStringHash($contents);
        $count = 0;

        foreach ($this->adapters as $adapter) {
            if ($adapter->head($hash)) {
                ++$count;
                continue;
            }

            if (!$adapter->initUpload($hash, strlen($contents), $filename, $mimetype, $usageType)) {
                continue;
            }

            if (!$adapter->addChunk($hash, $contents, $usageType)) {
                continue;
            }

            if ($adapter->finalizeUpload($hash, $usageType)) {
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

    public function initUploadFile(string $fileHash, int $fileSize, string $fileName, string $mimeType, int $usageType): int
    {
        $count = 0;
        foreach ($this->adapters as $adapter) {
            if ($adapter->initUpload($fileHash, $fileSize, $fileName, $mimeType, $usageType)) {
                ++$count;
            }
        }

        if ($count === 0) {
            throw new \RuntimeException(sprintf('Impossible to initiate the upload of an asset identified by the hash %s into at least one storage services', $fileHash));
        }

        return $count;
    }

    public function addChunk(string $hash, string $chunk, int $usageType): int
    {
        $count = 0;
        foreach ($this->adapters as $adapter) {
            if ($adapter->addChunk($hash, $chunk, $usageType)) {
                ++$count;
            }
        }

        if ($count === 0) {
            throw new \RuntimeException(sprintf('Impossible to add a chunk of an asset identified by the hash %s into at least one storage services', $hash));
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

    public function finalizeUpload(string $hash, int $size, int $usageType): int
    {
        $count = 0;
        foreach ($this->adapters as $adapter) {
            try {
                $handler = $adapter->read($hash, false);
            } catch (\Throwable $e) {
                continue;
            }

            $uploadedSize = $handler->getSize();
            if ($uploadedSize === null) {
                continue;
            }
            $computedHash = $this->computeStringHash($handler->getContents());

            if ($computedHash !== $hash) {
                throw new HashMismatchException($hash, $computedHash);
            }

            if ($uploadedSize !== $size) {
                throw new SizeMismatchException($hash, $size, $uploadedSize);
            }

            if ($adapter->finalizeUpload($hash, $usageType)) {
                ++$count;
            }
        }

        if ($count === 0) {
            throw new \RuntimeException(sprintf('Impossible finalize the upload of an asset identified by the hash %s into at least one storage services', $hash));
        }

        return $count;
    }

    public function saveFile(string $filename, int $usageType): string
    {
        $count = 0;
        $hash = $this->computeFileHash($filename);
        foreach ($this->adapters as $adapter) {
            if ($adapter->create($hash, $filename, $usageType)) {
                ++$count;
            }
        }

        if ($count === 0) {
            throw new \RuntimeException(sprintf('Impossible to a a file (%s) identified by the hash %s into at least one storage services', $filename, $hash));
        }

        return $hash;
    }

    public function remove(string $hash): int
    {
        $count = 0;
        foreach ($this->adapters as $adapter) {
            try {
                if ($adapter->remove($hash)) {
                    ++$count;
                }
            } catch (\Throwable $e) {
            }
        }
        return $count;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function saveConfig(array $config): string
    {
        $normalizedArray = ArrayTool::normalizeAndSerializeArray($config);
        if ($normalizedArray === false) {
            throw new \RuntimeException('Could not normalize config.');
        }
        return $this->saveContents($normalizedArray, 'assetConfig.json', 'application/json', StorageInterface::STORAGE_USAGE_CONFIG);
    }
}
