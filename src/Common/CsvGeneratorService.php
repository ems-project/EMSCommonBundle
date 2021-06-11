<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common;

use EMS\CommonBundle\Contracts\CsvGeneratorServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CsvGeneratorService implements CsvGeneratorServiceInterface
{
    public const CONTENT_DISPOSITION = 'disposition';
    public const CONTENT_FILENAME = 'filename';
    public const TABLE_CONTENT = 'table';

    /**
     * @param string[][] $config
     */
    public function generateCsv(array $config): StreamedResponse
    {
        $options = $this->resolveConfig($config);

        $response = new StreamedResponse(
            function () use ($options) {
                $handle = \fopen('php://output', 'r+');
                if (false === $handle) {
                    throw new \RuntimeException('Unexpected error while opening php://output');
                }
                foreach ($options[TABLE_CONTENT] as $row) {
                    \fputcsv($handle, $row);
                }
            }
        );

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', \sprintf('%s;filename="%s.csv"', $options[self::CONTENT_DISPOSITION], $options[self::CONTENT_FILENAME]));
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * @param array<mixed> $config
     *
     * @return array{table: string[][], filename: string, disposition: string}
     */
    private function resolveConfig(array $config): array
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefaults([
            'table' => [],
            self::CONTENT_FILENAME => 'csv_export',
            self::CONTENT_DISPOSITION => 'attachment',
        ]);
        $optionsResolver->setAllowedTypes('table', ['array']);
        $optionsResolver->setAllowedTypes(self::CONTENT_FILENAME, ['string']);
        $optionsResolver->setAllowedTypes(self::CONTENT_DISPOSITION, ['string']);
        $optionsResolver->setAllowedValues(self::CONTENT_DISPOSITION, ['attachment', 'inline']);
        /** @var array{table: string[][], filename: string, disposition: string} $options */
        $options = $optionsResolver->resolve($config);

        return $options;
    }
}
