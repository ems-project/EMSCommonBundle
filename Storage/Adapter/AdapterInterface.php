<?php

namespace EMS\CommonBundle\Storage\Adapter;

interface AdapterInterface
{
    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return bool
     */
    public function exists(string $hash, ?string $context = null): bool;

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return string
     */
    public function read(string $hash, ?string $context = null): string;

    /**
     * @param string      $hash
     * @param string      $content
     * @param string|null $context
     *
     * @return false|string
     */
    public function create(string $hash, string $content, ?string $context = null);

    /**
     * @return bool
     */
    public function health(): bool;
}
