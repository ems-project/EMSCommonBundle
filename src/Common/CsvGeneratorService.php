<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common;

use EMS\CommonBundle\Contracts\CsvGeneratorServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CsvGeneratorService implements CsvGeneratorServiceInterface
{
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
                foreach ($options['table'] as $row) {
                    \fputcsv($handle, $row);
                }
            }
        );

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', \sprintf('%s;filename="%s.csv"', $options['disposition'], $options['filename']));
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
            'filename' => 'csv_export',
            'disposition' => 'attachment',
        ]);
        $optionsResolver->setAllowedTypes('table', ['array']);
        $optionsResolver->setAllowedTypes('filename', ['string']);
        $optionsResolver->setAllowedTypes('disposition', ['string']);
        $optionsResolver->setAllowedValues('disposition', ['attachment', 'inline']);
        /** @var array{table: string[][], filename: string, disposition: string} $options */
        $options = $optionsResolver->resolve($config);

        return $options;
    }
}
