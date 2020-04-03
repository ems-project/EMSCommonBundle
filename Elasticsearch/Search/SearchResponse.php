<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Search;

use EMS\CommonBundle\Contracts\Elasticsearch\Document\DocumentCollectionInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Search\SearchResponseInterface;
use EMS\CommonBundle\Elasticsearch\Document\Document;
use EMS\CommonBundle\Elasticsearch\Document\DocumentCollection;

final class SearchResponse implements SearchResponseInterface
{
    /** @var array */
    private $hits;
    /** @var array */
    private $response;
    /** @var null|string */
    private $scrollId;
    /** @var int */
    private $total;

    public function __construct(array $response)
    {
        $this->hits = $response['hits']['hits'] ?? [];
        $this->response = $response;
        $this->scrollId = $response['_scroll_id'] ?? null;
        $this->total = $response['hits']['total'] ?? 0;
    }

    public function hasDocuments(): bool
    {
        return count($this->hits) > 0;
    }

    public function getDocuments(): iterable
    {
        foreach ($this->hits as $hit) {
            yield new Document($hit);
        }
    }

    public function getDocumentCollection(): DocumentCollectionInterface
    {
        return DocumentCollection::fromSearchResponse($this);
    }

    public function getScrollId(): ?string
    {
        return $this->scrollId;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getTotalDocuments(): int
    {
        return count($this->hits);
    }

    public function toArray(): array
    {
        return $this->response;
    }
}
