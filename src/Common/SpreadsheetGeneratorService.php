<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common;

use EMS\CommonBundle\Contracts\SpreadsheetGeneratorServiceInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SpreadsheetGeneratorService implements SpreadsheetGeneratorServiceInterface
{
    /**
     * @param array<mixed> $config
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function generateSpreadsheet(array $config): StreamedResponse
    {
        $config = $this->resolveOptions($config);

        switch ($config[self::WRITER]) {
            case self::XLSX_WRITER:
                $response = $this->getXlsxResponse($config);
                break;
            case self::CSV_WRITER:
                $response = $this->getCsvResponse($config);
                break;
            default:
                throw new \RuntimeException('Unknown Spreadsheet writer');
        }

        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * @param array<mixed> $config
     */
    private function buildUpSheets(array $config): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $i = 0;
        foreach ($config[self::SHEETS] as $sheetConfig) {
            $sheet = (0 === $i) ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet($i);
            $sheet->setTitle($sheetConfig['name']);
            $j = 1;
            foreach ($sheetConfig['rows'] as $row) {
                $k = 1;
                foreach ($row as $value) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($k).$j, Converter::stringify($value));
                    ++$k;
                }
                ++$j;
            }
            ++$i;
        }

        if (isset($config['active_sheet'])) {
            $spreadsheet->setActiveSheetIndex($config['active_sheet']);
        }

        return $spreadsheet;
    }

    /**
     * @return array<string, mixed>
     */
    private static function getDefaults(): array
    {
        return [
            self::CONTENT_FILENAME => 'spreadsheet',
            self::CONTENT_DISPOSITION => 'attachment',
            self::WRITER => self::XLSX_WRITER,
            'active_sheet' => 0,
        ];
    }

    /**
     * @param array<mixed> $config
     *
     * @return array{writer: string, filename: string, disposition: string, sheets: array}
     */
    private function resolveOptions(array $config): array
    {
        $defaults = self::getDefaults();

        $resolver = new OptionsResolver();
        $resolver->setDefaults($defaults);
        $resolver->setRequired([self::WRITER, self::CONTENT_FILENAME, self::SHEETS, self::CONTENT_DISPOSITION]);
        $resolver->setAllowedTypes(self::CONTENT_DISPOSITION, ['string']);
        $resolver->setAllowedValues(self::WRITER, [self::XLSX_WRITER, self::CSV_WRITER]);
        $resolver->setAllowedValues(self::CONTENT_DISPOSITION, ['attachment', 'inline']);

        /** @var array{writer: string, filename: string, disposition: string, sheets: array} $resolved */
        $resolved = $resolver->resolve($config);

        return $resolved;
    }

    /**
     * @param array{writer: string, filename: string, disposition: string, sheets: array} $config
     */
    private function getXlsxResponse(array $config): StreamedResponse
    {
        $spreadsheet = $this->buildUpSheets($config);

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', \sprintf('%s;filename="%s.xlsx"', $config[self::CONTENT_DISPOSITION], $config[self::CONTENT_FILENAME]));

        return $response;
    }

    /**
     * @param array{writer: string, filename: string, disposition: string, sheets: array} $config
     */
    private function getCsvResponse(array $config): StreamedResponse
    {
        if (1 !== \count($config[self::SHEETS])) {
            throw new \RuntimeException('Exactly one sheet is expected by the CSV writer');
        }

        $response = new StreamedResponse(
            function () use ($config) {
                $handle = \fopen('php://output', 'r+');
                if (false === $handle) {
                    throw new \RuntimeException('Unexpected error while opening php://output');
                }

                foreach ($config[self::SHEETS][0]['rows'] ?? [] as $row) {
                    \fputcsv($handle, $row);
                }
            }
        );

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', \sprintf('%s;filename="%s.csv"', $config[self::CONTENT_DISPOSITION], $config[self::CONTENT_FILENAME]));

        return $response;
    }
}
