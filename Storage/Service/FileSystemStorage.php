<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Service;

final class FileSystemStorage extends AbstractUrlStorage
{
    /** @var string */
    private $storagePath;

    /** @var string */
    private $directorySeparator;

    public function __construct(string $storagePath, string $directorySeparator = DIRECTORY_SEPARATOR)
    {
        $this->storagePath = $storagePath;
        $this->directorySeparator = $directorySeparator;
    }

    protected function getBaseUrl(): string
    {
        return $this->storagePath;
    }

    public function __toString(): string
    {
        return FileSystemStorage::class." ($this->storagePath)";
    }
}
