<?php

namespace EMS\CommonBundle\Tests\Unit\Elasticsearch\Document;

use EMS\CommonBundle\Elasticsearch\Document\DocumentCollection;
use EMS\CommonBundle\Elasticsearch\Response\ResponseInterface;
use EMS\CommonBundle\Elasticsearch\Document\DocumentInterface;

use PHPUnit\Framework\TestCase;

class DocumentCollectionTest extends TestCase
{
    /** @var \ResponseInterface&\PHPUnit\Framework\MockObject\MockObject $mockResponse */
    private $mockResponse;

    public function setUp()
    {
        $this->mockResponse = $this->createMock(ResponseInterface::class);
        $this->mockResponse->method('getDocuments')
            ->willReturn([
                $this->createMock(DocumentInterface::class),
                $this->createMock(DocumentInterface::class)
            ]);
    }

    public function testFromResponse()
    {
        $collection = DocumentCollection::fromResponse($this->mockResponse);
        $this->assertEquals(2, $collection->count());
    }

    public function testGetIterator()
    {
        $collection = DocumentCollection::fromResponse($this->mockResponse);
        $this->assertEquals(2, \count($collection->getIterator()));
    }
}
