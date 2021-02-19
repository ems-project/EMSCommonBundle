<?php

namespace EMS\CommonBundle\Common;

use EMS\CommonBundle\Contracts\SpreadsheetGeneratorServiceInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SpreadsheetGeneratorService implements SpreadsheetGeneratorServiceInterface
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array<mixed> $config
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function generateSpreadsheet(array $config): void
    {
        // $config = json_decode('{"filename":"export","writer":"xlsx","active_sheet":0,"sheets":[{"name":"Export form","color":"#FF0000","rows":[["a1","a2"],["b1","b3"]]},{"name":"Export form sheet 2","rows":[["a1","a2"],["b1","b3"]]}]}', true);

        $defaults = self::getDefaults();

        $resolver = new OptionsResolver();
        $resolver->setDefaults($defaults);
        $resolver->setAllowedValues('writer', ['xlsx']);

        $resolver->resolve($config);

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

        $writer = new Xlsx($spreadsheet);
        \header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        \header('Content-Disposition: attachment; filename="'.\urlencode('filename.xlsx').'"');
        $writer->save('php://output');
    }

    /**
     * @return array<string, mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'filename' => 'spreadsheet.xlsx',
            'writer' => 'xlsx',
            'active_sheet' => 0,
            'sheets' => null,
        ];
    }
}
