<?php

namespace EMS\CommonBundle\Storage;

use EMS\CommonBundle\Storage\Service\FileSystemStorage;
use EMS\CommonBundle\Storage\Service\HttpStorage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Symfony\Component\Config\FileLocatorInterface;

class StorageManager
{
    /**
     * @var StorageInterface[]
     */
    private $adapters = [];

    /**
     * @var StorageInterface[]
     */
    private $cacheAdapters = [];

    /**
     * @var FileLocatorInterface
     */
    private $fileLocator;

    /**
     * @var string
     */
    private $hashAlgo;

    public function __construct(FileLocatorInterface $fileLocator, iterable $adapters, iterable $cacheAdapters, string $hashAlgo, ?string $storagePath, ?string $backendUrl)
    {
        $this->fileLocator = $fileLocator;
        $this->hashAlgo = $hashAlgo;

        foreach ($adapters as $adapter) {
            $this->adapters[] = $adapter;
        }

        foreach ($cacheAdapters as $cacheAdapter) {
            $this->cacheAdapters[] = $cacheAdapter;
        }

        if ($storagePath) {
            $this->addAdapter(new FileSystemStorage($storagePath));
        }
        if ($backendUrl) {
            $this->addAdapter(new HttpStorage($backendUrl, '/public/file/'));
        }
    }

    /**
     * @return StorageInterface[]|iterable
     */
    public function getAdapters()
    {
        return $this->adapters;
    }


    /**
     * @param StorageInterface $storageAdapter
     * @return StorageManager
     */
    public function addAdapter(StorageInterface $storageAdapter)
    {

        $this->adapters[] = $storageAdapter;
        if ($storageAdapter->supportCacheStore()) {
            $this->cacheAdapters[] = $storageAdapter;
        }
        return $this;
    }

    /**
     * @param string $hash
     * @param string|null $context
     *
     * @return resource
     */
    public function getResource(string $hash, ?string $context = null)
    {
        return $this->read($this->adapters, $hash, $context);
    }

    /**
     * @param string $hash
     * @param null|string $context
     * @return string
     */
    public function getContents(string $hash, ?string $context = null): string
    {
        $resource = $this->read($this->adapters, $hash, $context);
        $out = '';
        while (!feof($resource)) {
            $out .= fread($resource, 8192);
        }

        fclose($resource);
        return $out;
    }

    /**
     * @deprecated
     * @param string $hash
     * @param string|null $context
     *
     * @return string
     */
    public function getFile(string $hash, ?string $context = null): string
    {
        @trigger_error("StorageManager::getFile is deprecated use the getContents or the getResource function", E_USER_DEPRECATED);
        $resource = $this->read($this->adapters, $hash, $context);
        //TODO:use (if neede) http://php.net/manual/en/class.spltempfileobject.php
        $filename = tempnam(sys_get_temp_dir(), 'EMS');
        file_put_contents($filename, $resource);
        return $filename;
    }

    /**
     * @deprecated
     * @param string $hash
     * @param string|null $context
     * @return string
     */
    public function getCacheFile(string $hash, ?string $context = null): string
    {
        @trigger_error("StorageManager::getCacheFile is deprecated use the getContents or the getResource function", E_USER_DEPRECATED);
        $resource = $this->read($this->cacheAdapters, $hash, $context);
        $filename = tempnam(sys_get_temp_dir(), 'EMS');
        file_put_contents($filename, $resource);
        return $filename;
    }

    /**
     * @param string $hash
     * @param string $fileName
     * @param string|null $context
     *
     * @return bool
     */
    public function createCacheFile(string $hash, string $fileName, ?string $context = null): bool
    {
        foreach ($this->cacheAdapters as $cacheAdapter) {
            if ($cacheAdapter->create($hash, $fileName, $context)) {
                return true;
            }
        }

        return false;
    }

    public function getLastCacheDate(string $hash, ?string $context = null): ?\DateTime
    {
        @trigger_error(sprintf('The "%s::getLastCacheDate" method is deprecated and should not more be used.', StorageManager::class), E_USER_DEPRECATED);

        $lastDate = null;

        foreach ($this->cacheAdapters as $cacheAdapter) {
            $date = $cacheAdapter->getLastUpdateDate($hash, $context);

            if ($date && ($lastDate === null || $date > $lastDate)) {
                $lastDate = $date;
            }
        }

        return $lastDate;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getPublicImage(string $name): string
    {
        return $this->fileLocator->locate('@EMSCommonBundle/Resources/public/images/' . $name);
    }

    /**
     * @return string
     */
    public function getHashAlgo()
    {
        return $this->hashAlgo;
    }

    public function saveContents(string $contents, string $filename, string $mimetype, string $context = null, int $shouldBeSavedOnXServices = 1)
    {
        $hash = hash($this->hashAlgo, $contents);
        $out = 0;

        /**@var StorageInterface $service */
        foreach ($this->getAdapters() as $service) {
            if ($shouldBeSavedOnXServices != 0 && $out >= $shouldBeSavedOnXServices) {
                break;
            }

            if ($service->head($hash, $context)) {
                ++$out;
                continue;
            }

            if (!$service->initUpload($hash, strlen($contents), $filename, $mimetype, $context)) {
                continue;
            }

            if (!$service->addChunk($hash, $contents, $context)) {
                continue;
            }

            if ($service->finalizeUpload($hash, $context)) {
                ++$out;
            }
        }

        return $hash;
    }

    public function computeResourceHash($handler): string
    {
        $ctx = hash_init($this->hashAlgo);
        while (!feof($handler)) {
            hash_update($ctx, fread($handler, 8192));
        }
        return hash_final($ctx);
    }

    public function computeStringHash($string): string
    {
        return hash($this->hashAlgo, $string);
    }

    public function computeFileHash($filename): string
    {
        return hash_file($this->hashAlgo, $filename);
    }

    public function cacheResource($resource, string $hash, string $context, string $filename, string $mimeType, int $shouldBeSavedOnXServices = 0)
    {
        $out = 0;
        $size = 0;
        $stat = fstat($resource);
        if (isset($stat['size'])) {
            $size = $stat['size'];
        }

        /**@var StorageInterface $service */
        foreach ($this->getAdapters() as $service) {
            if ($shouldBeSavedOnXServices != 0 && $out >= $shouldBeSavedOnXServices) {
                break;
            }


            if ($service->head($hash, $context)) {
                ++$out;
                continue;
            }

            if (!$service->initUpload($hash, $size, $filename, $mimeType, $context)) {
                continue;
            }

            while (!feof($resource)) {
                $str = fread($resource, 8192);
                if (!$service->addChunk($hash, $str, $context)) {
                    continue;
                }
            }

            if ($service->finalizeUpload($hash, $context)) {
                ++$out;
            }

            rewind($resource);
        }

        return $out;
    }

    public function initUploadFile(string $fileHash, int $fileSize, string $fileName, string $mimeType, int $uploadMinimumNumberOfReplications): int
    {
        $loopCounter = 0;
        foreach ($this->getAdapters() as $adapter) {
            if ($adapter->initUpload($fileHash, $fileSize, $fileName, $mimeType) && ++$loopCounter >= $uploadMinimumNumberOfReplications) {
                break;
            }
        }
        return $loopCounter;
    }

    /**
     * @param StorageInterface[]|iterable $adapters
     * @param string $hash
     * @param string|null $context
     *
     * @return resource
     */
    private function read(iterable $adapters, string $hash, ?string $context = null)
    {
        foreach ($adapters as $adapter) {
            if ($adapter->head($hash, $context)) {
                return $adapter->read($hash, $context);
            }
        }

        throw new NotFoundException($hash);
    }
}
