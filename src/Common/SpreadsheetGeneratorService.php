<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common;

use EMS\CommonBundle\Contracts\SpreadsheetGeneratorServiceInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SpreadsheetGeneratorService implements SpreadsheetGeneratorServiceInterface
{
    public const WRITER = 'writer';
    public const SHEETS = 'sheets';
    public const CONTENT_FILENAME = 'filename';
    public const CONTENT_DISPOSITION = 'disposition';

    /**
     * @param array<mixed> $config
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function generateSpreadsheet(array $config): StreamedResponse
    {
        $config = $this->resolveOptions($config);

        $spreadsheet = $this->buildUpSheets($config);

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', \sprintf('%s;filename="%s.xlsx"', $config[self::CONTENT_DISPOSITION], $config[self::CONTENT_FILENAME]));
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
            $sheet = (0 === $i) ? $sheet = $spreadsheet->getActiveSheet() : $spreadsheet->createSheet($i);
            $sheet->setTitle($sheetConfig['name']);
            $j = 1;
            foreach ($sheetConfig['rows'] as $row) {
                $k = 'A';
                foreach ($row as $value) {
                    $sheet->setCellValue($k.$j, Converter::stringify($value));
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
            self::WRITER => 'xlsx',
            'active_sheet' => 0,
        ];
    }

    /**
     * @param array<mixed> $config
     *
     * @return array<mixed>
     */
    private function resolveOptions(array $config): array
    {
        $defaults = self::getDefaults();

        $resolver = new OptionsResolver();
        $resolver->setDefaults($defaults);
        $resolver->setRequired([self::WRITER, self::CONTENT_FILENAME, self::SHEETS, self::CONTENT_DISPOSITION]);
        $resolver->setAllowedTypes(self::CONTENT_DISPOSITION, ['string']);
        $resolver->setAllowedValues(self::WRITER, ['xlsx']);
        $resolver->setAllowedValues(self::CONTENT_DISPOSITION, ['attachment', 'inline']);

        return $resolver->resolve($config);
    }
}
