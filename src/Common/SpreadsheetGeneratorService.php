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
        \header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        \header('Content-Disposition: attachment; filename="'.\urlencode($config['filename'].'.xlsx').'"');
        $writer->save('php://output');
        exit;
    }

    /**
     * @param array<mixed> $config
     */
    public function buildUpSheets(array $config): Spreadsheet
    {
        // dd($config);
        $spreadsheet = new Spreadsheet();

        $i = 0;
        foreach ($config['sheets'] as $sheetConfig) {
            $sheet = (0 === $i) ? $sheet = $spreadsheet->getActiveSheet() : $spreadsheet->createSheet($i);
            $sheet->setTitle($sheetConfig['name']);
            $j = 1;
            foreach ($sheetConfig['rows'] as $row) {
                $k = 'A';
                foreach ($row as $value) {
                    $sheet->setCellValue($k.$j, $value);
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
    public static function getDefaults(): array
    {
        return [
            'filename' => 'spreadsheet',
            'writer' => 'xlsx',
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
        $resolver->setRequired(['writer', 'filename', 'sheets']);
        $resolver->setAllowedValues('writer', ['xlsx']);

        return $resolver->resolve($config);
    }
}
