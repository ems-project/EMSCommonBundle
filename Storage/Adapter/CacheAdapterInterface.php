<?php

namespace EMS\CommonBundle\Storage\Adapter;

interface CacheAdapterInterface extends AdapterInterface
{
    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return null|\DateTime
     */
    public function getLastUpdateDate(string $hash, ?string $context = null): ?\DateTime;
}