<?php

namespace EMS\CommonBundle\Storage\Service;

interface StorageInterface
{

    /**
     * @param string $hash
     * @param bool|string $cacheContext
     * @return bool
     */
    public function head($hash, $cacheContext = false);


    /**
     * @return bool
     */
    public function health(): bool;


    /**
     * Use to display the service in the console
     * @return string
     */
    public function __toString();

    /**
     * @param string $hash
     * @param string $filename
     * @param bool|string $cacheContext
     * @return bool
     */
    public function create($hash, $filename, $cacheContext = false);
//    public function create(string $hash, string $content, ?string $context = null);

    /**
     * @param string $hash
     * @param bool|string $cacheContext
     * @param bool $confirmed
     * @return resource|bool
     */
    public function read($hash, $cacheContext = false, $confirmed=true);
    //public function read(string $hash, ?string $context = null): string;


    /**
     * @param string $hash
     * @param bool|string $cacheContext
     * @return integer
     */
    public function getSize($hash, $cacheContext = false);

    /**
     * @return bool
     */
    public function supportCacheStore();

    /**
     * @return bool
     */
    public function clearCache();

    /**
     * @param $hash
     * @return bool
     */
    public function remove($hash);

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