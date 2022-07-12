<?php

namespace EMS\CommonBundle\Tests\Unit\Json;

use EMS\CommonBundle\Json\JsonMenuNested;
use PHPUnit\Framework\TestCase;

class JsonMenuNestedTest extends TestCase
{
    private JsonMenuNested $jsonMenuNested;

    protected function setUp(): void
    {
        $this->jsonMenuNested = JsonMenuNested::fromStructure(file_get_contents(__DIR__.'/json_menu_nested_1.json'));
    }

    public function testMethods(): void
    {
        $this->assertSame('root', $this->jsonMenuNested->getId());
        $this->assertSame('root', $this->jsonMenuNested->getLabel());

        $player1Item = $this->jsonMenuNested->getItemById('0d19f63f-30c8-4bad-b0fd-ecc9d7b16c48');

        $this->assertSame('player 1', $player1Item->getLabel());
        $this->assertSame('player', $player1Item->getType());
    }

    public function testLoopJsonMenuNested(): void
    {
        $this->assertTrue($this->jsonMenuNested->hasChildren());
        $this->assertTrue($this->jsonMenuNested->isRoot());

        $count = 0;
        foreach ($this->jsonMenuNested as $item) {
            $this->assertInstanceOf(JsonMenuNested::class, $item);
            ++$count;
        }
        $this->assertEquals(6, $count);
    }


}
