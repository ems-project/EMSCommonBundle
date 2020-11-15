<?php

namespace EMS\CommonBundle\Elasticsearch\Aggregation;

class Aggregation
{
    /** @var array<mixed> */
    private $buckets;
    /** @var int */
    private $count;
    /** @var array<mixed> */
    private $raw;

    /**
     * @param array<mixed> $aggregation
     */
    public function __construct(array $aggregation)
    {
        $this->buckets = $aggregation['buckets'] ?? [];
        $this->count = $aggregation['doc_count'] ?? 0;
        $this->raw = $aggregation;
    }

    /**
     * @return iterable<Bucket>
     */
    public function getBuckets(): iterable
    {
        foreach ($this->buckets as $bucket) {
            yield new Bucket($bucket);
        }
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @deprecated
     * @return array<mixed>
     */
    public function getRaw(): array
    {
        @trigger_error("Aggregation::getRaw is deprecated use the others getters", E_USER_DEPRECATED);
        return $this->raw;
    }
}
