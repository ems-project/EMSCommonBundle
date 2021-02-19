<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts;

interface SpreadsheetGeneratorServiceInterface
{
    /**
     * @param array<mixed> $config
     */
    public function generateSpreadsheet(array $config): void;
}
