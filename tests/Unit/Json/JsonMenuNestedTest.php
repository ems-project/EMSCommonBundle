<?php

namespace EMS\CommonBundle\Tests\Unit\Helper\Text;

use EMS\CommonBundle\Json\JsonMenuNested;
use PHPUnit\Framework\TestCase;

class JsonMenuNestedTest extends TestCase
{
    /** @var JsonMenuNested */
    private $jsonMenuNested;

    protected function setUp(): void
    {
        $data = '{
                    "id": "102a603e-b2ab-499d-b1d3-687a2e4ee168",
                    "type": "theme",
                    "object": 
                    {
                        "label_nl": "testExampleAdrienFR",
                        "label_fr": "testExampleAdrienNL"
                    },
                    "label": "testadrienLabel",
                    "children": 
                    [
                        {
                            "id": "7b4f228f-3d04-4eb0-a826-aeaa1e8bc8aa",
                            "object": {
                            "label_nl": "testDocNL",
                            "label_fr": "testDocFR"
                            },
                            "type": "theme_document",
                            "label": "testDoc"
                        }
                    ]
                }';
        $data = \json_decode($data,true);
        $this->jsonMenuNested = new JsonMenuNested($data);
    }

    public function testGetter(): void
    {
        self::assertSame( "102a603e-b2ab-499d-b1d3-687a2e4ee168", $this->jsonMenuNested->getId());
        self::assertSame("theme", $this->jsonMenuNested->getType());
        self::assertSame("testadrienLabel", $this->jsonMenuNested->getLabel());
    }

    public function testGetIterator(): void
    {
        $count = 0;
        foreach ($this->jsonMenuNested->getIterator() as $json) {
            self::assertInstanceOf(JsonMenuNested::class, $json);
            ++$count;
        }
        $this->assertEquals(1, $count);
    } 

    public function testHasChildren(): void
    {
        self::assertTrue($this->jsonMenuNested->hasChildren(), "Has Children true");
    }

    public function testIsRoot(): void
    {
        self::assertTrue($this->jsonMenuNested->isRoot(), "is Root true");
    }
}