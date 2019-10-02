<?php

namespace EMS\CommonBundle\Storage\Service;

use Psr\Http\Message\StreamInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractUrlStorage implements StorageInterface
{

    /**
     * Returns the base url of the storage service
     * @return string
     */
    abstract protected function getBaseUrl(): string;

    /**
     * returns the a file path or a resource url that can be handled by file function such as fopen
     */
    protected function getPath(string $hash, ?string $cacheContext = null, bool $confirmed = true, string $ds = '/'): string
    {
        $folderName = $this->getBaseUrl();

        if (!$confirmed) {
            $folderName .= $ds . 'uploads';
        }

        //isolate cached files
        if ($cacheContext) {
            $folderName .= $ds . 'cache' . $ds . $cacheContext;
        }

        //in order to avoid a folder with a to big number of files in
        if ($confirmed) {
            $folderName .= $ds . substr($hash, 0, 3);
        }

        //create folder if missing
        if (!file_exists($folderName)) {
            mkdir($folderName, 0777, true);
        }

        return $folderName . $ds . $hash;
    }

    /**
     * @param string $hash
     * @param string $cacheContext
     * @return bool
     */
    public function head(string $hash, ?string $cacheContext = null): bool
    {
        return file_exists($this->getPath($hash, $cacheContext));
    }

    /**
     * @param string $hash
     * @param string $filename
     * @param string $cacheContext
     * @return bool
     */
    public function create(string $hash, string $filename, ?string $cacheContext = null): bool
    {
        return copy($filename, $this->getPath($hash, $cacheContext));
    }

    /**
     * @return bool
     */
    public function supportCacheStore(): bool
    {
        return true;
    }

    /**
     * @return resource|bool|StreamInterface
     */
    public function read(string $hash, ?string $cacheContext = null, bool $confirmed = true)
    {
        $out = $this->getPath($hash, $cacheContext, $confirmed);
        if (!file_exists($out)) {
            return false;
        }

        return fopen($out, 'rb');
    }

    /**
     * @deprecated
     * @param string $hash
     * @param null|string $context
     * @return \DateTime|null
     */
    public function getLastUpdateDate(string $hash, ?string $context = null): ?\DateTime
    {
        @trigger_error("getLastUpdateDate is deprecated.", E_USER_DEPRECATED);
        $path = $this->getPath($hash, $context);
        if (file_exists($path)) {
            $time = @filemtime($path);
            return $time ? \DateTime::createFromFormat('U', (string) $time) : null;
        }
        return null;
    }


    /**
     * @return bool
     */
    public function health(): bool
    {
        return is_dir($this->getBaseUrl());
    }

    /**
     * @param string $hash
     * @param null|string $cacheContext
     * @return int
     */
    public function getSize(string $hash, ?string $cacheContext = null): ?int
    {
        $path = $this->getPath($hash, $cacheContext);
        if (file_exists($path)) {
            return @filesize($path);
        }
        return null;
    }

    /**
     * Use to display the service in the console
     * @return string
     */
    abstract public function __toString(): string;

    /**
     * @return bool
     */
    public function clearCache(): bool
    {
        $fileSystem = new Filesystem();
        $fileSystem->remove($this->getBaseUrl() . '/cache');
        return true;
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function remove(string $hash): bool
    {
        $file = $this->getPath($hash);
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }


    /**
     * @param string $hash
     * @param int $size
     * @param string $name
     * @param string $type
     * @param null|string $context
     * @return bool
     */
    public function initUpload(string $hash, int $size, string $name, string $type, ?string $context = null): bool
    {
        $path = $this->getPath($hash, $context, false);
        return file_put_contents($path, "") !== false;
    }

    /**
     * @param string      $hash
     * @param string      $chunk
     * @param string|null $context
     *
     * @return bool
     */
    public function addChunk(string $hash, string $chunk, ?string $context = null): bool
    {
        $path = $this->getPath($hash, $context, false);
        if (!file_exists($path)) {
            throw new NotFoundHttpException('temporary file not found');
        }

        $file = fopen($path, "a");
        $result = fwrite($file, $chunk);

        fflush($file);
        fclose($file);

        if ($result === false || $result != strlen($chunk)) {
            return false;
        }

        return true;
    }

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return bool
     */
    public function finalizeUpload(string $hash, ?string $context = null): bool
    {
        $source = $this->getPath($hash, $context, false);
        $destination  = $this->getPath($hash, $context);
        try {
            return \rename($source, $destination);
        } catch (\Throwable $e) {
            //TODO: add log info or notice
        }
        try {
            return \copy($source, $destination);
        } catch (\Throwable $e) {
            //TODO: add log info or notice
        }
        return false;
    }
}
