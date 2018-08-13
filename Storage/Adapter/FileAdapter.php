<?php

namespace EMS\CommonBundle\Storage\Adapter;

use Symfony\Component\Filesystem\Filesystem;

class FileAdapter implements AdapterInterface
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
    public function exists(string $sha1): bool
    {
        return file_exists($this->getFilename($sha1));
    }

    /**
     * @inheritdoc
     */
    public function read(string $sha1): string
    {
        return $this->getFilename($sha1);
    }

    /**
     * @inheritdoc
     */
    public function create(string $sha1, string $content): string
    {
        $filename = $this->getFilename($sha1);

        if (!$this->filesystem->exists($this->getDir($sha1))) {
            $this->filesystem->mkdir($this->getDir($sha1));
        }

        $this->filesystem->touch($filename);
        $this->filesystem->dumpFile($filename, $content);

        return $filename;
    }

    /**
     * @inheritdoc
     */
    public function health(): bool
    {
        return file_exists($this->path);
    }

    /**
     * @param string $sha1
     *
     * @return string
     */
    private function getFilename(string $sha1): string
    {
        return $this->getDir($sha1).'/'.$sha1;
    }

    /**
     * @param $sha1
     *
     * @return string
     */
    private function getDir($sha1): string
    {
        return $this->path.'/'.substr($sha1, 0, 3);
    }
}