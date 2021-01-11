<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage;

/**
 * @implements \IteratorAggregate<array>
 */
final class FileCollection implements \IteratorAggregate
{
    /** @var array<mixed, mixed> */
    private $files;
    /** @var StorageManager */
    private $storageManager;

    /**
     * FileCollection constructor.
     * @param array<mixed, mixed> $files
     * @param StorageManager $storageManager
     */
    public function __construct(array $files, StorageManager $storageManager)
    {
        $this->files = $files;
        $this->storageManager = $storageManager;
    }

    public function getIterator()
    {
        foreach ($this->files as $file) {
            $file['content'] = $this->storageManager->getContents($file['sha1']);
            yield $file;
        }
    }
}
