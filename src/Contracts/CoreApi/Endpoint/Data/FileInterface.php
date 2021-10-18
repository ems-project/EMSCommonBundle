<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi\Endpoint\Data;

interface FileInterface
{
    public function hashFile(string $filename): string;

    public function initUpload(string $hash, int $size, string $filename, string $mimetype): int;

    public function addChunk(string $hash, string $chunk): int;

    public function uploadFile(string $realPath, string $mimeType = null): ?string;

    public function headFile(string $realPath): bool;
}
