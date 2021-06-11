<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

interface CsvGeneratorServiceInterface
{
    public const CONTENT_DISPOSITION = 'disposition';
    public const CONTENT_FILENAME = 'filename';
    public const TABLE_CONTENT = 'table';

    /**
     * @param array<mixed> $config
     */
    public function generateCsv(array $config): StreamedResponse;
}
