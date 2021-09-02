<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Service;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractUrlStorage implements StorageInterface
{
    /** @var int */
    private $usage;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger, int $usage)
    {
        $this->logger = $logger;
        $this->usage = $usage;
    }

    abstract protected function getBaseUrl(): string;

    protected function initDirectory(string $filename): void
    {
        if ($this->usage >= self::STORAGE_USAGE_EXTERNAL) {
            return;
        }
        $directoryName = \dirname($filename);
        if (!\file_exists($directoryName)) {
            try {
                \mkdir($directoryName, 0777, true);
            } catch (\Throwable $e) {
                $this->logger->warning('Not able to create a {directoryName} folder', ['directoryName' => $directoryName]);
            }
        }
    }

    protected function getUploadPath(string $hash, string $ds = '/'): string
    {
        return \join($ds, [
            $this->getBaseUrl(),
            'uploads',
            $hash,
        ]);
    }

    protected function getPath(string $hash, string $ds = '/'): string
    {
        return \join($ds, [
            $this->getBaseUrl(),
            \substr($hash, 0, 3),
            $hash,
        ]);
    }

    public function head(string $hash): bool
    {
        return \file_exists($this->getPath($hash));
    }

    public function create(string $hash, string $filename): bool
    {
        $path = $this->getPath($hash);
        $this->initDirectory($path);

        return \copy($filename, $path);
    }

    public function read(string $hash, bool $confirmed = true): StreamInterface
    {
        if ($confirmed) {
            $out = $this->getPath($hash);
        } else {
            $out = $this->getUploadPath($hash);
        }
        if (!\file_exists($out)) {
            throw new NotFoundHttpException($hash);
        }
        $resource = \fopen($out, 'rb');
        if (!\is_resource($resource)) {
            throw new NotFoundHttpException($hash);
        }

        return new Stream($resource);
    }

    public function health(): bool
    {
        return \is_dir($this->getBaseUrl());
    }

    public function getSize(string $hash): int
    {
        $path = $this->getPath($hash);

        if (!\file_exists($path)) {
            throw new NotFoundHttpException($hash);
        }

        $size = @\filesize($path);
        if (false === $size) {
            throw new NotFoundHttpException($hash);
        }

        return $size;
    }

    abstract public function __toString(): string;

    public function remove(string $hash): bool
    {
        $file = $this->getPath($hash);
        if (\file_exists($file)) {
            \unlink($file);
        }

        return true;
    }

    public function initUpload(string $hash, int $size, string $name, string $type): bool
    {
        $path = $this->getUploadPath($hash);
        $this->initDirectory($path);

        return false !== \file_put_contents($path, '');
    }

    public function addChunk(string $hash, string $chunk): bool
    {
        $path = $this->getUploadPath($hash);
        if (!\file_exists($path)) {
            throw new NotFoundHttpException('temporary file not found');
        }

        $file = \fopen($path, 'a');
        if (false === $file) {
            return false;
        }

        $result = \fwrite($file, $chunk);
        \fflush($file);
        \fclose($file);

        if (false === $result || $result != \strlen($chunk)) {
            return false;
        }

        return true;
    }

    public function finalizeUpload(string $hash): bool
    {
        $source = $this->getUploadPath($hash);
        $destination = $this->getPath($hash);
        $this->initDirectory($destination);
        try {
            return \rename($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->info('Rename {source} to {destination} failed: {message} in service {serviceName}', [
                'source' => $source,
                'destination' => $destination,
                'message' => $e->getMessage(),
                'serviceName' => $this->__toString(),
            ]);
        }
        try {
            return \copy($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->warning('Copy {source} to {destination} failed: {message}in service {serviceName}', [
                'source' => $source,
                'destination' => $destination,
                'message' => $e->getMessage(),
                'serviceName' => $this->__toString(),
            ]);
        }

        return false;
    }

    public function getUsage(): int
    {
        return $this->usage;
    }
}
