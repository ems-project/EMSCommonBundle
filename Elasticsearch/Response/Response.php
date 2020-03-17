<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Response;

use EMS\CommonBundle\Elasticsearch\Document\Document;
use EMS\CommonBundle\Elasticsearch\Document\DocumentCollection;

final class Response implements ResponseInterface
{
    /** @var int */
    private $total;

    /** @var array */
    private $hits;

    /** @var null|string */
    private $scrollId;

    public function __construct(array $response)
    {
        $this->total = $response['hits']['total'] ?? 0;
        $this->hits = $response['hits']['hits'] ?? [];
        $this->scrollId = $response['_scroll_id'] ?? null;
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

    public function getDocumentCollection(): DocumentCollection
    {
        return DocumentCollection::fromResponse($this);
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
}