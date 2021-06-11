<?php

namespace EMS\CommonBundle\Tests\Unit\Common;

use EMS\CommonBundle\Common\CsvGeneratorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvGeneratorTest extends TestCase
{
    /** @var CsvGeneratorService */
    private $csvSheetGenerator;

    public function testConfigToCsv(): void
    {
        $config = \json_decode('{"filename":"test-export","disposition":"inline","table":[["apple","banana"],["pineapple","strawberry"],["àï$@,& & \\" \' ! @ # $ €", "foobar"]]}', true);
        /** @var StreamedResponse $csv */
        $csv = $this->callMethod($this->csvSheetGenerator, 'generateCsv', [$config]);

        //https://github.com/symfony/symfony/issues/25005
        \ob_start();
        $csv->send();
        $getContent = \ob_get_contents();
        \ob_end_clean();

        $lines = \explode(PHP_EOL, $getContent);
        $this->assertCount(4, $lines);
        $this->assertSame('apple,banana', $lines[0]);
        $this->assertSame('pineapple,strawberry', $lines[1]);
        $this->assertSame('"àï$@,& & "" \' ! @ # $ €",foobar', $lines[2]);
        $this->assertSame('', $lines[3]);
    }

    /**
     * @param mixed $object
     *
     * @return mixed
     */
    private function callMethod($object, string $method, array $parameters = [])
    {
        try {
            $className = \get_class($object);
            $reflection = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new \Exception($e->getMessage());
        }

        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    protected function setUp(): void
    {
        $this->csvSheetGenerator = new CsvGeneratorService();
        parent::setUp();
    }
}
