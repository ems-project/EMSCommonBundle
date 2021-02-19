<?php

namespace EMS\CommonBundle\Tests\Unit\Common;

use EMS\CommonBundle\Common\SpreadsheetGeneratorService;
use PHPUnit\Framework\TestCase;

class SpreadsheetGeneratorTest extends TestCase
{
    /** @var SpreadsheetGeneratorService */
    private $spreadSheetGenerator;

    protected function setUp(): void
    {
        $this->spreadSheetGenerator = new SpreadsheetGeneratorService();
        parent::setUp();
    }

    public function testConfigToSpreadsheet(): void
    {
        $config = \json_decode('{"filename":"export","writer":"xlsx","active_sheet":0,"sheets":[{"name":"Export form","color":"#FF0000","rows":[["apple","banana"],["pineapple","strawberry"]]},{"name":"Export form sheet 2","rows":[["a1","a2"],["b1","b3"]]}]}', true);
        $this->assertSame('Export form', $this->spreadSheetGenerator->buildUpSheets($config)->getActiveSheet()->getTitle());
        $this->assertSame('pineapple', $this->spreadSheetGenerator->buildUpSheets($config)->getActiveSheet()->getCell('A2')->getValue());
    }
}
