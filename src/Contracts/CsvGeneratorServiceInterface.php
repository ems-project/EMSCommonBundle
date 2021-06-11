<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

interface CsvGeneratorServiceInterface
{
    /**
     * @param array<mixed> $config
     */
    public function generateCsv(array $config): StreamedResponse;
}
