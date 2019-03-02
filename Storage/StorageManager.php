<?php

namespace EMS\CommonBundle\Storage;

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

    public function __construct(FileLocatorInterface $fileLocator, iterable $adapters, iterable $cacheAdapters)
    {
        $this->fileLocator = $fileLocator;

        foreach ($adapters as $adapters) {
            $this->adapters[] = $adapters;
        }

        foreach ($cacheAdapters as $cacheAdapter) {
            $this->cacheAdapters[] = $cacheAdapter;
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
    public function addAdapter(StorageInterface $storageAdapter) {

        $this->adapters[] = $storageAdapter;
        if($storageAdapter->supportCacheStore())
        {
            $this->cacheAdapters[] = $storageAdapter;
        }
        return $this;
    }

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return resource
     */
    public function getResource(string $hash, ?string $context = null)
    {
        return $this->read($this->adapters, $hash, $context);
    }

    /**
     * @deprecated
     * @param string      $hash
     * @param string|null $context
     *
     * @return string
     */
    public function getFile(string $hash, ?string $context = null): string
    {
        $resource = $this->read($this->adapters, $hash, $context);
        $filename = tempnam(sys_get_temp_dir(), 'EMS');
        file_put_contents($filename, $resource);
        return $filename;
    }

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return resource
     */
    public function getCacheFile(string $hash, ?string $context = null)
    {
        $resource = $this->read($this->cacheAdapters, $hash, $context);
        $filename = tempnam(sys_get_temp_dir(), 'EMS');
        file_put_contents($filename, $resource);
        return $filename;
    }

    /**
     * @param string      $hash
     * @param string      $fileName
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

    /**
     * @param string $hash
     * @param string|null $context
     *
     * @return null|\DateTime
     */
    public function getLastCacheDate(string $hash, ?string $context = null): ?\DateTime
    {
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
        return  $this->fileLocator->locate('@EMSCommonBundle/Resources/public/images/'.$name);
    }

    /**
     * @param StorageInterface[]|iterable $adapters
     * @param string                      $hash
     * @param string|null                 $context
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