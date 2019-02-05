<?php

namespace EMS\CommonBundle\Storage;

use EMS\CommonBundle\Storage\Adapter\AdapterInterface;
use EMS\CommonBundle\Storage\Adapter\CacheAdapterInterface;
use Symfony\Component\Config\FileLocatorInterface;

class StorageManager
{
    /**
     * @var AdapterInterface[]
     */
    private $adapters = [];

    /**
     * @var CacheAdapterInterface[]
     */
    private $cacheAdapters = [];

    /**
     * @var FileLocatorInterface
     */
    private $fileLocator;

    public function __construct(FileLocatorInterface $fileLocator, iterable $adapters, iterable $cacheAdapters)
    {
        $this->fileLocator = $fileLocator;
        $this->adapters = $adapters;

        foreach ($cacheAdapters as $cacheAdapter) {
            if (!$cacheAdapter instanceof CacheAdapterInterface) {
                throw new \InvalidArgumentException(sprintf('Adapter %s can not be used for caching', get_class($cacheAdapter)));
            }
        }

        $this->cacheAdapters = $cacheAdapters;
    }

    /**
     * @return AdapterInterface[]|iterable
     */
    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return string
     */
    public function getFile(string $hash, ?string $context = null): string
    {
        return $this->read($this->adapters, $hash, $context);
    }

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return string
     */
    public function getCacheFile(string $hash, ?string $context = null): string
    {
        return $this->read($this->cacheAdapters, $hash, $context);
    }

    /**
     * @param string      $hash
     * @param string      $content
     * @param string|null $context
     *
     * @return bool
     */
    public function createCacheFile(string $hash, string $content, ?string $context = null): bool
    {
        foreach ($this->cacheAdapters as $cacheAdapter) {
            if ($cacheAdapter->create($hash, $content, $context)) {
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
     * @param AdapterInterface[]|iterable $adapters
     * @param string                      $hash
     * @param string|null                 $context
     *
     * @return string
     */
    private function read(iterable $adapters, string $hash, ?string $context = null): string
    {
        foreach ($adapters as $adapter) {
            if ($adapter->exists($hash, $context)) {
                return $adapter->read($hash, $context);
            }
        }

        throw new NotFoundException($hash);
    }
}