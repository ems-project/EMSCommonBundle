<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Response;

use Elastica\ResultSet;
use EMS\CommonBundle\Elasticsearch\Aggregation\Aggregation;
use EMS\CommonBundle\Elasticsearch\Document\Document;
use EMS\CommonBundle\Elasticsearch\Document\DocumentCollection;

final class Response implements ResponseInterface
{
    /** @var int */
    private $total;

    /** @var array<string, mixed> */
    private $hits;

    /** @var string|null */
    private $scrollId;
    /** @var bool */
    private $accurate;
    /** @var array<mixed> */
    private $aggregations;

    /**
     * @param array<mixed> $response
     */
    private function __construct(array $response)
    {
        $this->total = $response['hits']['total']['value'] ?? $response['hits']['total'] ?? 0;
        $this->accurate = true;
        if (\is_array($response['hits']['total'] ?? null) && 'eq' !== $response['hits']['total']['relation'] ?? null) {
            $this->accurate = false;
        }
        $this->hits = $response['hits']['hits'] ?? [];
        $this->aggregations = $response['aggregations'] ?? [];
        $this->scrollId = $response['_scroll_id'] ?? null;
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function fromArray(array $document): Response
    {
        return new self($document);
    }

    public static function fromResultSet(ResultSet $result): Response
    {
        return new self($result->getResponse()->getData());
    }

    public function hasDocuments(): bool
    {
        return \count($this->hits) > 0;
    }

    public function getDocuments(): iterable
    {
        foreach ($this->hits as $hit) {
            yield Document::fromArray($hit);
        }
    }

    public function getAggregation(string $name): ?Aggregation
    {
        if (isset($this->aggregations[$name])) {
            return new Aggregation($name, $this->aggregations[$name]);
        }

        return null;
    }

    /**
     * @return iterable|Aggregation[]
     */
    public function getAggregations(): iterable
    {
        foreach ($this->aggregations as $name => $aggregation) {
            yield new Aggregation($name, $aggregation);
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

    public function getFormattedTotal(): string
    {
        $format = '%s';
        if (!$this->accurate) {
            $format = '≥%s';
        }

        return \sprintf($format, $this->total);
    }

    public function getTotalDocuments(): int
    {
        return \count($this->hits);
    }

    public function isAccurate(): bool
    {
        return $this->accurate;
    }
}
