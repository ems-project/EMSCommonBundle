<?php

namespace EMS\CommonBundle\Storage;

use EMS\CommonBundle\Storage\Adapter\AdapterInterface;

class StorageManager
{
    /**
     * @var AdapterInterface[]
     */
    private $adapters = [];

    /**
     * @param iterable|AdapterInterface[] $adapters
     */
    public function __construct(iterable $adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * @return AdapterInterface[]|iterable
     */
    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * @param string $sha1
     *
     * @return string
     *
     * @throws NotFoundException
     */
    public function getFile(string $sha1): string
    {
        foreach ($this->adapters as $adapter) {
            if (!$adapter->exists($sha1)) {
                continue;
            }

            return $adapter->read($sha1);
        }

        throw new NotFoundException($sha1);
    }
}