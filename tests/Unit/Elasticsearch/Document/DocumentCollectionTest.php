<?php

namespace EMS\CommonBundle\Tests\Unit\Elasticsearch\Document;

use EMS\CommonBundle\Elasticsearch\Document\DocumentCollection;
use EMS\CommonBundle\Elasticsearch\Response\ResponseInterface;
use EMS\CommonBundle\Elasticsearch\Document\DocumentInterface;
use PHPUnit\Framework\TestCase;

class DocumentCollectionTest extends TestCase
{
    /** @var ResponseInterface */
    private $mockResponse;

    protected function setUp(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getDocuments')
            ->willReturn([
                $this->createMock(DocumentInterface::class),
                $this->createMock(DocumentInterface::class)
            ]);
        $this->mockResponse = $mockResponse;
    }

    public function testFromResponse(): void
    {
        $collection = DocumentCollection::fromResponse($this->mockResponse);
        $this->assertEquals(2, $collection->count());
    }

    public function testGetIterator(): void
    {
        $collection = DocumentCollection::fromResponse($this->mockResponse);

        if ($collection->getIterator() instanceof \Traversable) {
            $this->assertEquals(2, \iterator_count($collection->getIterator()));
            return;
        }

        $this->assertEquals(2, \count($collection->getIterator()));
    }
}
