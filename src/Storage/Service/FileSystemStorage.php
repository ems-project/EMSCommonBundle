<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Service;

use Psr\Log\LoggerInterface;

class FileSystemStorage extends AbstractUrlStorage
{
    /** @var string */
    private $storagePath;

    /** @var string */
    private $directorySeparator;

    public function __construct(LoggerInterface $logger, string $storagePath, int $usage, int $hotSynchronizeLimit = 0, string $directorySeparator = DIRECTORY_SEPARATOR)
    {
        parent::__construct($logger, $usage, $hotSynchronizeLimit);
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

    /**
     * @return null
     */
    protected function getContext()
    {
        return null;
    }
}
