<?php

namespace EMS\CommonBundle\Storage\Service;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractUrlStorage implements StorageInterface
{

    abstract protected function getBaseUrl(): string;

    protected function getPath(string $hash, bool $confirmed = true, string $ds = '/'): string
    {
        $folderName = $this->getBaseUrl() . $ds . $confirmed ? substr($hash, 0, 3) : 'uploads';

        if (!file_exists($folderName)) {
            mkdir($folderName, 0777, true);
        }

        return $folderName . $ds . $hash;
    }

    public function head(string $hash): bool
    {
        return file_exists($this->getPath($hash));
    }

    public function create(string $hash, string $filename): bool
    {
        return copy($filename, $this->getPath($hash));
    }

    public function read(string $hash, bool $confirmed = true): StreamInterface
    {
        $out = $this->getPath($hash, $confirmed);
        if (!file_exists($out)) {
            throw new NotFoundHttpException($hash);
        }
        $resource = fopen($out, 'rb');
        if (!is_resource($resource)) {
            throw new NotFoundHttpException($hash);
        }

        return new Stream($resource);
    }


    public function health(): bool
    {
        return is_dir($this->getBaseUrl());
    }

    public function getSize(string $hash): int
    {
        $path = $this->getPath($hash);

        if (!\file_exists($path)) {
            throw new NotFoundHttpException($hash);
        }

        $size = @filesize($path);
        if ($size === false) {
            throw new NotFoundHttpException($hash);
        }

        return $size;
    }

    abstract public function __toString(): string;

    public function remove(string $hash): bool
    {
        $file = $this->getPath($hash);
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }


    public function initUpload(string $hash, int $size, string $name, string $type): bool
    {
        $path = $this->getPath($hash, false);
        return file_put_contents($path, "") !== false;
    }

    public function addChunk(string $hash, string $chunk): bool
    {
        $path = $this->getPath($hash, false);
        if (!file_exists($path)) {
            throw new NotFoundHttpException('temporary file not found');
        }

        $file = fopen($path, "a");
        if ($file === false) {
            return false;
        }

        $result = fwrite($file, $chunk);
        fflush($file);
        fclose($file);

        if ($result === false || $result != strlen($chunk)) {
            return false;
        }

        return true;
    }

    public function finalizeUpload(string $hash): bool
    {
        $source = $this->getPath($hash, false);
        $destination  = $this->getPath($hash);
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
