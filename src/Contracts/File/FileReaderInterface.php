<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\File;

interface FileReaderInterface
{
    public function getData(string $filename, bool $skipFirstRow = false);
}
