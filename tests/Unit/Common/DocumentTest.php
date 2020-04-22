<?php

namespace EMS\CommonBundle\Tests\Unit\Common;

use EMS\CommonBundle\Common\Document;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase
{
    protected function setUp()
    {
        $this->document = new Document('docString', 'docStr', []);
    }

    public function testDocumentGetcontentType()
    {
        $this->assertEquals('docString', $this->document->getContentType());
    }

    public function testDocumentGetOuuid()
    {
        $this->assertEquals('docStr', $this->document->getOuuid());
    }

    public function testDocumentGetSource()
    {
        $this->assertEquals([], $this->document->getSource());
    }
}
