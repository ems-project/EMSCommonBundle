<?php

namespace EMS\CommonBundle\Tests\Unit\Common;

use EMS\CommonBundle\Common\Converter;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{

    /** @var Converter */
    private $converter;

    protected function setUp()
    {
        $this->converter = new Converter();
        parent::setUp();
    }

    /**
     *  format: [text,text]
     */
    public function strProvider(): array
    {
        return  [
            ['test', 'test'],
            ['TEST', 'test'],
            ['À', 'a'],
            ['È', 'e'],
            ['[-test\+&test]', 'testtest'],
        ];
    }

    /**
     * @dataProvider strProvider
     */
    public function testToAscii(string $str, string $expected)
    {
        self::assertSame($expected, $this->converter->toAscii($str));
    }

    /**
     *  format: [int,text]
     */
    public function byteProvider(): array
    {
        return  [
            [243, '243 B', '243 B', '243 B'],
            [2496, '2.44 KB', '2 KB', '2.4375 KB'],
            [24962496, '23.81 MB', '24 MB', '23.8061 MB'],
            [249624962496, '232.48 GB', '232 GB', '232.4814 GB'],
            [2496249624962496, '2270.33 TB', '2270 TB', '2270.3258 TB'],

        ];
    }

    /**
     * @dataProvider byteProvider
     */
    public function testBytes(int $byte, string $expected, string $expected2, string $expected3)
    {
        //$valueMB = 24962496;

        self::assertSame($expected, $this->converter->formatBytes($byte));
        self::assertSame($expected2, $this->converter->formatBytes($byte, 0));
        self::assertSame($expected3, $this->converter->formatBytes($byte, 4));
    }
}
