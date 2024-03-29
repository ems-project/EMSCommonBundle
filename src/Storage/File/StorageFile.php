<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\File;

use EMS\Helpers\File\TempFile;
use Psr\Http\Message\StreamInterface;

class StorageFile implements FileInterface
{
    private ?TempFile $tempFile = null;

    public function __construct(private readonly string $hash, private readonly StreamInterface $stream)
    {
    }

    public function __destruct()
    {
        if (null === $this->tempFile) {
            return;
        }

        $this->tempFile->clean();
    }

    public function getContent(): string
    {
        return $this->stream->getContents();
    }

    public function getFilename(): string
    {
        if (null === $this->tempFile) {
            $this->tempFile = TempFile::createNamed($this->hash);
            $this->tempFile->loadFromStream($this->stream);
        }

        return $this->tempFile->path;
    }
}
