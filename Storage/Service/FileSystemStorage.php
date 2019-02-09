<?php

namespace EMS\CommonBundle\Storage\Service;


use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use function file_exists;
use function filesize;
use function fopen;
use function touch;
use function unlink;

class FileSystemStorage extends AbstractUrlStorage
{

    /**
     * Store the base url or the directory path
     * @var string
     */
    private $storagePath;

    /**
     * Just an exception for the windows systems
     * @var string
     */
    private $directorySeparator;

    /**
     * FileSystemStorage constructor.
     * @param string $storagePath
     * @param string $directorySeparator
     */
    public function __construct(string $storagePath, string $directorySeparator=DIRECTORY_SEPARATOR)
    {
        $this->storagePath = $storagePath;
        $this->directorySeparator = $directorySeparator;
    }

    /**
     * @inheritdoc
     */
    protected function getBaseUrl(): string
    {
        return $this->storagePath;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return FileSystemStorage::class . " ($this->storagePath)";
    }
}
