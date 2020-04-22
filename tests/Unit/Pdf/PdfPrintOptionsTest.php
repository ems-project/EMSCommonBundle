<?php

namespace EMS\CommonBundle\Tests\Unit\PdfPrintOptionsTest;

use EMS\CommonBundle\Service\Pdf\PdfPrintOptions;
use PHPUnit\Framework\TestCase;

class PdfPrintOptionsTest extends TestCase
{
    protected function setUp()
    {
        $this->printTest = new PdfPrintOptions([true,true,true,'portrait','a4']);
        $this->printTest = new PdfPrintOptions([]);
    }

    public function testGetOrientation()
    {
        $this->assertEquals('portrait', $this->printTest->getOrientation());
    }

    public function testGetSize()
    {
        $this->assertEquals('a4', $this->printTest->getSize());
    }

    public function testIsAttachment()
    {
        $this->assertEquals(true, $this->printTest->isAttachment());
    }

    public function testIsCompress()
    {
        $this->assertEquals(true, $this->printTest->isCompress());
    }

    public function testIsHtml5Parsing()
    {
        $this->assertEquals(true, $this->printTest->isHtml5Parsing());
    }

    //No test on private methods
}
