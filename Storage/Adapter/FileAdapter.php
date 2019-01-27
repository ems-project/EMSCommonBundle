<?php

namespace EMS\CommonBundle\Storage\Adapter;

use Symfony\Component\Filesystem\Filesystem;

class FileAdapter implements CacheAdapterInterface
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->filesystem = new Filesystem();
    }

    /**
     * @inheritdoc
     */
    public function exists(string $hash, ?string $context = null): bool
    {
        return file_exists($this->getFilename($hash, $context));
    }

    /**
     * @inheritdoc
     */
    public function read(string $hash, ?string $context = null): string
    {
        return $this->getFilename($hash, $context);
    }

    /**
     * @inheritdoc
     */
    public function create(string $hash, string $content, ?string $context = null)
    {
        $filename = $this->getFilename($hash, $context);

        if (!$this->filesystem->exists($this->getDir($hash, $context))) {
            $this->filesystem->mkdir($this->getDir($hash, $context));
        }

        $this->filesystem->touch($filename);
        $this->filesystem->dumpFile($filename, $content);

        return $this->read($hash, $context);
    }

    /**
     * @inheritdoc
     */
    public function health(): bool
    {
        return file_exists($this->path);
    }

    /**
     * @inheritdoc
     */
    public function getLastUpdateDate(string $hash, ?string $context = null): ?\DateTime
    {
        if (!$this->exists($hash, $context)) {
            return null;
        }

        $time = @filemtime($this->getFilename($hash, $context));

        return $time ? \DateTime::createFromFormat('U', $time) : null;
    }

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return string
     */
    private function getFilename(string $hash, ?string $context = null): string
    {
        return $this->getDir($hash, $context).'/'.$hash;
    }

    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return string
     */
    private function getDir($hash, ?string $context = null): string
    {
        $dir = substr($hash, 0, 3);

        return $context ? sprintf('%s/%s/%s', $this->path, $context, $dir) : $this->path.'/'.$dir;
    }
}