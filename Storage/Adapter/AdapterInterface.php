<?php

namespace EMS\CommonBundle\Storage\Adapter;

interface AdapterInterface
{
    /**
     * @param string $sha1
     *
     * @return bool
     */
    public function exists(string $sha1): bool;

    /**
     * @param string $sha1
     *
     * @return string
     */
    public function read(string $sha1): string;

    /**
     * @return bool
     */
    public function health(): bool;
}