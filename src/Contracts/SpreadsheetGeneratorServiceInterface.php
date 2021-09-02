<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

interface SpreadsheetGeneratorServiceInterface
{
    public const WRITER = 'writer';
    public const XLSX_WRITER = 'xlsx';
    public const CSV_WRITER = 'csv';
    public const SHEETS = 'sheets';
    public const CONTENT_FILENAME = 'filename';
    public const CONTENT_DISPOSITION = 'disposition';

    /**
     * @param array<mixed> $config
     */
    public function generateSpreadsheet(array $config): StreamedResponse;
}
