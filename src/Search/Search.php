<?php

namespace EMS\CommonBundle\Search;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Aggregation\Terms;
use Elastica\Query\AbstractQuery;
use Elastica\Search as ElasticaSearch;
use Elastica\Suggest;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;

class Search
{
    /** @var string[] */
    private array $sourceIncludes = [];
    /** @var string[] */
    private array $sourceExcludes = [];
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
    /** @var array<mixed>|null */
    private $sort = null;
    /** @var AbstractQuery|null */
    private $postFilter = null;
    /** @var Suggest */
    private $suggest = null;
    /** @var array<mixed>|null */
    private $highlight = null;

    private ?string $regex = null;

    /**
     * @param string[] $indices
     */
    public function __construct(array $indices, AbstractQuery $query = null)
    {
        $this->indices = $indices;
        $this->query = $query;
    }

    public function hasSources(): bool
    {
        return \count($this->sourceIncludes) > 0 || \count($this->sourceExcludes) > 0;
    }

    /**
     * @return string[]|array{includes: string[], excludes: string[]}
     */
    public function getSources(): array
    {
        if (\count($this->sourceExcludes) > 0) {
            return \array_filter([
                'includes' => $this->sourceIncludes,
                'excludes' => $this->sourceExcludes,
            ]);
        }

        return $this->sourceIncludes;
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
     * @param array<mixed> $sources
     */
    public function setSources(array $sources): void
    {
        if (0 === \count($sources)) {
            $this->sourceIncludes = [];

            return;
        }

        if (isset($sources['includes']) || isset($sources['excludes'])) {
            $this->sourceIncludes = $sources['includes'] ?? [];
            $this->sourceExcludes = $sources['excludes'] ?? [];

            return;
        }

        $this->sourceIncludes = \array_merge($sources, EMSSource::REQUIRED_FIELDS);
    }

    /**
     * @param string[] $sourceExcludes
     */
    public function setSourceExcludes(array $sourceExcludes): void
    {
        $this->sourceExcludes = $sourceExcludes;
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
     * @param array<mixed>|null $sort
     */
    public function setSort(?array $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * @return array<mixed>
     */
    public function getScrollOptions(): array
    {
        return [
            ElasticaSearch::OPTION_SIZE => $this->size,
        ];
    }

    /**
     * @return array<mixed>
     */
    public function getCountOptions(): array
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

    public function setPostFilter(?AbstractQuery $postFilter): void
    {
        $this->postFilter = $postFilter;
    }

    public function getPostFilter(): ?AbstractQuery
    {
        return $this->postFilter;
    }

    public function getSuggest(): ?Suggest
    {
        return $this->suggest;
    }

    public function setSuggest(Suggest $suggest): void
    {
        $this->suggest = $suggest;
    }

    /**
     * @return array<mixed>|null
     */
    public function getHighlight(): ?array
    {
        return $this->highlight;
    }

    /**
     * @param array<mixed> $highlight
     */
    public function setHighlight(array $highlight): void
    {
        $this->highlight = $highlight;
    }

    public function getRegex(): ?string
    {
        return $this->regex;
    }

    public function setRegex(?string $regex): void
    {
        $this->regex = $regex;
    }
}
