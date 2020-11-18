<?php

namespace EMS\CommonBundle\Storage\Service;

class FileSystemStorage extends AbstractUrlStorage
{

    /** @var string */
    private $storagePath;

    /** @var string */
    private $directorySeparator;

    public function __construct(string $storagePath, bool $readOnly, bool $toSkip, string $directorySeparator = DIRECTORY_SEPARATOR)
    {
        parent::__construct($readOnly, $toSkip);
        $this->storagePath = $storagePath;
        $this->directorySeparator = $directorySeparator;
    }

    protected function getBaseUrl(): string
    {
        return $this->storagePath;
    }

    public function __toString(): string
    {
        return FileSystemStorage::class . " ($this->storagePath)";
    }
}
