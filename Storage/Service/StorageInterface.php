<?php

namespace EMS\CommonBundle\Storage\Service;

interface StorageInterface
{

    /**
     * @param string $hash
     * @param string $cacheContext
     * @return bool
     */
    public function head(string $hash, ?string $cacheContext = null):bool;


    /**
     * @return bool
     */
    public function health():bool;


    /**
     * Use to display the service in the console
     * @return string
     */
    public function __toString():string;

    /**
     * @param string $hash
     * @param string $filename
     * @param string|null $cacheContext
     * @return bool
     */
    public function create(string $hash, string $filename, ?string $cacheContext = null):bool;

    /**
     * @param string $hash
     * @param string|null $cacheContext
     * @param bool $confirmed
     * @return resource|null
     */
    public function read(string $hash, ?string $cacheContext = null, bool $confirmed = true);


    /**
     * @param string $hash
     * @param null|string $cacheContext
     * @return int
     */
    public function getSize(string $hash, ?string $cacheContext = null): ?int;

    /**
     * @return bool
     */
    public function supportCacheStore();

    /**
     * @return bool
     */
    public function clearCache();

    /**
     * @param string $hash
     * @return bool
     */
    public function remove(string $hash):bool;

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return null|\DateTime
     */
    public function getLastUpdateDate(string $hash, ?string $context = null): ?\DateTime;



    /**
     * @param string      $hash
     * @param integer     $size
     * @param string      $name
     * @param string      $type
     * @param string|null $context
     *
     * @return bool
     */
    public function initUpload(string $hash, int $size, string $name, string $type, ?string $context = null): bool ;

    /**
     * @param string      $hash
     * @param string      $chunk
     * @param string|null $context
     *
     * @return bool
     */
    public function addChunk(string $hash, string $chunk, ?string $context = null): bool ;

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return bool
     */
    public function finalizeUpload(string $hash, ?string $context = null): bool ;



}