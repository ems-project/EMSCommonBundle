<?php

namespace EMS\CommonBundle\Elasticsearch\Aggregation;

class Bucket
{
    /** @var string|null */
    private $key;
    /** @var int */
    private $count;
    /** @var array<string, mixed> */
    private $raw;

    /**
     * @param array<string, mixed> $bucket
     */
    public function __construct(array $bucket)
    {
        $this->key = $bucket['key'] ?? null;
        $this->count = $bucket['doc_count'] ?? 0;
        $this->raw = $bucket;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return array<mixed>
     */
    public function getRaw(): array
    {
        return $this->raw;
    }
}
