<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

use EMS\CommonBundle\Elasticsearch\Response\ResponseInterface;

final class DocumentCollection implements DocumentCollectionInterface
{
    /** @var array */
    private $documents;

    private function __construct()
    {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        $collection = new static();

        foreach ($response->getDocuments() as $document) {
            $collection->add($document);
        }

        return $collection;
    }

    public function add(DocumentInterface $document): void
    {
        $this->documents[] = $document;
    }

    public function count(): int
    {
        return count($this->documents);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->documents);
    }
}
