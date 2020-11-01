<?php

namespace EMS\CommonBundle\Search;

use Elastica\Query\AbstractQuery;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;

class Search
{
    /** @var string[] */
    private $sources = [];
    /** @var string[] */
    private $contentTypes = [];
    /** @var AbstractQuery */
    private $query;
    /** @var string[] */
    private $indices;
    /** @var int */
    private $size = 10;
    /** @var int */
    private $from = 0;

    /**
     * @param string[] $indices
     */
    public function __construct(array $indices, AbstractQuery $query)
    {
        $this->indices = $indices;
        $this->query = $query;
    }

    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * @return string[]
     */
    public function getContentTypes(): array
    {
        return $this->contentTypes;
    }

    public function getQuery(): AbstractQuery
    {
        return $this->query;
    }

    /**
     * @param string[] $sources
     */
    public function setSources(array $sources): void
    {
        $this->sources = \array_merge($sources, [EMSSource::FIELD_CONTENT_TYPE]);
    }

    /**
     * @param string[] $contentTypes
     */
    public function setContentTypes(array $contentTypes): void
    {
        $this->contentTypes = $contentTypes;
    }

    /**
     * @return string[]
     */
    public function getIndices(): array
    {
        return $this->indices;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getFrom(): int
    {
        return $this->from;
    }

    public function setFrom(int $from): void
    {
        $this->from = $from;
    }
}
