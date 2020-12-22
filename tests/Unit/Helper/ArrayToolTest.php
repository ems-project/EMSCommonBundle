<?php

namespace EMS\CommonBundle\Tests\Unit\Helper\Text;

use Elastica\JSON;
use EMS\CommonBundle\Helper\ArrayTool;
use PHPUnit\Framework\TestCase;

class EncoderTest extends TestCase
{

    /** @var ArrayTool */
    private $arrayTool;

    protected function setUp(): void
    {
        $this->arrayTool = new ArrayTool();
        parent::setUp();
    }

     /**
     * format: [text,text].
     *
     * @return array<array<string>>
     */
    public function arrayProvider(): array
    {
        return [
            ['1',[],'',2,"3","string",'4',],
        ];
    }

    /**
     * format: [].
     *
     * @return array[] //<array<string>>
     */
    public function normalizeArrayProvider(): array
    {
        return array(
            array(
                array(
                    'id' => 1,
                    'data' => '1',
                ),
            ),
            array(
                array(
                    'id' => 2,
                    'data' => '',
                ),
            ),
            array(
                array(
                    'id' => 3,
                    'data' => '2',
                ),
            ),
            array(
                array(
                    'id' => 4,
                    'data' => '3',
                ),
            ),
            array(
                array(
                    'id' => 5,
                    'data' => 'string',
                ),
            ),
            array(
                array(
                    'id' => 6,
                    'data' => '4',
                ),
            ),
        );
    }

/*         return [
            'array' => [
                [[0] => '1'], //['test1' => '1']
                [[2] => ''],
                [[3] => '2'],
                [[4] => '3'],
                [[5] => 'string' ],
                [[6] => '4'],
            ],
        ];
    }

    public function dataProvider() {
        return array(                       // data sets
            array(                          // data set 0
                array(                      // first argument
                    'id' => 1,
                    'description' => 'this',
                ),
                'foo',                      // second argument
            ),
        );
    }  */

     /**
     * format: [text,text].
     *
     * @return json
     */
    public function jsonProvider()
    {
        return [
            'json' => [
                '{"0":"1","2":"","3":2,"4":"3","5":"string","6":"4"}'
            ],
        ]; 
    }

    /**
     * @dataProvider arrayProvider
     * @dataProvider normalizeArrayProvider
     */
    public function testNormalizeArray(string $provided, string $expected): void
    {
        self::assertSame($expected, $this->arrayTool->normalizeArray($provided));
    }

    /**
     * @dataProvider jsonProvider
     * @dataProvider arrayProvider
     */
    public function testNormalizeAndSerializeArray(string $expected, string $provided): void
    {
        $testArray = $this->arrayTool->normalizeArray($provided);
        self::assertSame($expected, $this->arrayTool->normalizeAndSerializeArray($testArray));
    }
}