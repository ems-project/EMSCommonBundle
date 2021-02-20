<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

interface SpreadsheetGeneratorServiceInterface
{
    /**
     * @param array<mixed> $config
     */
    public function generateSpreadsheet(array $config): StreamedResponse;
}
