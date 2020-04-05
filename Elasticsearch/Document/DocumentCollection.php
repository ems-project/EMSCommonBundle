<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

use EMS\CommonBundle\Contracts\Elasticsearch\Document\DocumentCollectionInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Document\DocumentInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Search\SearchResponseInterface;

final class DocumentCollection implements DocumentCollectionInterface
{
    /** @var array|DocumentInterface[] */
    private $documents;

    private function __construct()
    {
    }

    public static function fromSearchResponse(SearchResponseInterface $response): self
    {
        $collection = new static();

        foreach ($response->getDocuments() as $document) {
            $collection->add($document);
        }

        return $collection;
    }

    public function count(): int
    {
        return count($this->documents);
    }

    public function getIds(): array
    {
        return array_map(function (DocumentInterface $document) {
            return $document->getId();
        }, $this->documents);
    }

    public function first(): ?DocumentInterface
    {
        return $this->documents[0] ?? null;
    }

    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->documents);
    }

    private function add(DocumentInterface $document): void
    {
        $this->documents[] = $document;
    }
}
