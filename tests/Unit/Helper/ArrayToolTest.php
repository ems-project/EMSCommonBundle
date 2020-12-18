<?php

namespace EMS\CommonBundle\Tests\Unit\Helper\Text;

use EMS\CommonBundle\Helper\ArrayTool;
use PHPUnit\Framework\TestCase;

class EncoderTest extends TestCase
{
    /** @var array  */
    private $arrayTest = array('test1' => 1, 'test2' => 2, 'test3' => 3, 'test4' => 4, 'test5' => 5);

    /** @var ArrayTool */
    private $arrayTool;

    protected function setUp(): void
    {
        $this->arrayTool = new ArrayTool();
        parent::setUp();
    }

    public function testNormalizeAndSerializeArray(array $arrayTest): void
    {
        $jsonTest = $this->arrayTool->normalizeAndSerializeArray($arrayTest);
        echo json_encode($arrayTest,0);
        echo $jsonTest;
        self::assertSame(json_encode($arrayTest,0), $jsonTest );
    }

    public function testNormalizeArray(): void
    {

    }
}