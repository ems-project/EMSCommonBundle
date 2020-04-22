<?php

namespace EMS\CommonBundle\Tests\Unit\Pdf;

use EMS\CommonBundle\Service\Pdf\Pdf as PdfPdf;
use PHPUnit\Framework\TestCase;

class PdfTest extends TestCase
{
    protected function setUp()
    {
        $this->pdfTest = new PdfPdf('pdffString', 'pdfStr');
    }

    public function testGetFilename()
    {
        $this->assertEquals('pdffString', $this->pdfTest->getFilename());
    }

    public function testGetHtml()
    {
        $this->assertEquals('pdfStr', $this->pdfTest->getHtml());
    }
}
