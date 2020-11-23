<?php

namespace EMS\CommonBundle\Search;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\Terms;
use Elastica\Query\AbstractQuery;
use Elastica\Search as ElasticaSearch;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;

class Search
{
    /** @var string[] */
    private $sources = [];
    /** @var string[] */
    private $contentTypes = [];
    /** @var AbstractAggregation[] */
    private $aggregations = [];
    /** @var AbstractQuery|null */
    private $query;
    /** @var string[] */
    private $indices;
    /** @var int */
    private $size = 10;
    /** @var int */
    private $from = 0;
    /** @var null|array<mixed>  */
    private $sort = null;

    /**
     * @param string[] $indices
     */
    public function __construct(array $indices, AbstractQuery $query = null)
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

    public function getQuery(): ?AbstractQuery
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

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function setFrom(int $from): void
    {
        $this->from = $from;
    }

    /**
     * @return array{size: int, from: int}
     */
    public function getSearchOptions(): array
    {
        return [
            ElasticaSearch::OPTION_SIZE => $this->size,
            ElasticaSearch::OPTION_FROM => $this->from,
        ];
    }

    /**
     * @return array<mixed>|null
     */
    public function getSort(): ?array
    {
        return $this->sort;
    }

    /**
     * @param array<mixed> $sort
     */
    public function setSort(array $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * @return array<mixed>
     */
    public function getScrollOptions(): array
    {
        return [];
    }

    /**
     * @param AbstractAggregation[] $aggregations
     */
    public function addAggregations(array $aggregations): void
    {
        $this->aggregations = \array_merge($this->aggregations, $aggregations);
    }

    public function addAggregation(AbstractAggregation $aggregation): void
    {
        $this->aggregations[] = $aggregation;
    }

    /**
     * @return AbstractAggregation[]
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    public function addTermsAggregation(string $name, string $field, int $size = 20): void
    {
        $termsAggregation = new Terms($name);
        $termsAggregation->setField($field);
        $termsAggregation->setSize($size);
        $this->addAggregation($termsAggregation);
    }
}
