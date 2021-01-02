<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service;

use Elastica\Aggregation\Terms as TermsAggregation;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Terms;
use Elastica\Response;
use Elastica\ResultSet;
use Elastica\Scroll;
use Elastica\Search as ElasticaSearch;
use Elasticsearch\Endpoints\Cluster\Health;
use Elasticsearch\Endpoints\Count;
use Elasticsearch\Endpoints\Indices\Analyze;
use Elasticsearch\Endpoints\Indices\Mapping\GetField;
use Elasticsearch\Endpoints\Info;
use Elasticsearch\Endpoints\Scroll as ScrollEndpoints;
use EMS\CommonBundle\Elasticsearch\Client;
use EMS\CommonBundle\Elasticsearch\Aggregation\ElasticaAggregation;
use EMS\CommonBundle\Elasticsearch\Document\Document;
use EMS\CommonBundle\Elasticsearch\Document\Document as ElasticsearchDocument;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;
use EMS\CommonBundle\Elasticsearch\Elastica\Scroll as EmsScroll;
use EMS\CommonBundle\Elasticsearch\Exception\NotFoundException;
use EMS\CommonBundle\Elasticsearch\Exception\NotSingleResultException;
use EMS\CommonBundle\Search\Search;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ElasticaService
{
    /** @var LoggerInterface */
    private $logger;
    /** @var Client */
    private $client;

    public function __construct(LoggerInterface $logger, Client $client)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function getHealthStatus(string $waitForStatus = null, string $timeout = '10s', ?string $index = null): string
    {
        try {
            $health = $this->getClusterHealth($waitForStatus, $timeout, $index);
            $status = $health['status'] ?? 'red';
            if (!\is_string($status)) {
                throw new \RuntimeException('Unexpected not string status');
            }

            return $status;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return 'red';
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getClusterHealth(string $waitForStatus = null, string $timeout = '10s', ?string $index = null): array
    {
        $query = [
            'timeout' => $timeout,
        ];
        if (null !== $waitForStatus) {
            $query['wait_for_status'] = $waitForStatus;
        }
        $endpoint = new Health();
        if (null !== $index) {
            $endpoint->setIndex($index);
        }
        $endpoint->setParams($query);

        return $this->client->requestEndpoint($endpoint)->getData();
    }

    public function singleSearch(Search $search): Document
    {
        $resultSet = $this->search($search);
        if (0 === $resultSet->count()) {
            throw new NotSingleResultException(0);
        }
        $result = $resultSet->offsetGet(0);
        if (1 !== $resultSet->count() || null === $result) {
            throw new NotSingleResultException($resultSet->count());
        }

        return Document::fromResult($result);
    }

    /**
     * @return array<mixed>
     */
    public function getClusterInfo(): array
    {
        $endpoint = new Info();

        return $this->client->requestEndpoint($endpoint)->getData();
    }

    /**
     * @param string[] $indexes
     * @param string[] $terms
     * @param string[] $contentTypes
     */
    public function generateTermsSearch(array $indexes, string $field, array $terms, array $contentTypes = []): Search
    {
        $query = new Terms($field, $terms);
        if (empty($contentTypes)) {
            $query = $this->filterByContentTypes($query, $contentTypes);
        }

        return new Search($indexes, $query);
    }

    public function getBoolQuery(): BoolQuery
    {
        return new BoolQuery();
    }

    /**
     * @param string[] $terms
     */
    public function getTermsQuery(string $field, array $terms): Terms
    {
        return new Terms($field, $terms);
    }

    public function search(Search $search): ResultSet
    {
        return $this->createElasticaSearch($search, $search->getSearchOptions())->search();
    }

    public function scroll(Search $search, string $expiryTime = '1m'): Scroll
    {
        $search = clone $search;
        $search->setSort(null);
        $elasticaSearch = $this->createElasticaSearch($search, $search->getScrollOptions());

        return new EmsScroll($elasticaSearch, $expiryTime);
    }

    public function scrollById(Search $search, string $expiryTime = '1m'): ResultSet
    {
        $search = clone $search;
        $search->setSort(null);
        $elasticaSearch = $this->createElasticaSearch($search, $search->getScrollOptions());
        $elasticaSearch->setOption(ElasticaSearch::OPTION_SCROLL, $expiryTime);

        return $elasticaSearch->search();
    }

    public function nextScroll(string $scrollId, string $expiryTime = '1m'): Response
    {
        $endpoint = new ScrollEndpoints();
        $endpoint->setScroll($expiryTime);
        $endpoint->setScrollId($scrollId);

        return $this->client->requestEndpoint($endpoint);
    }

    public function count(Search $search): int
    {
        $elasticSearch = $this->createElasticaSearch($search, $search->getCountOptions());
        $query = $elasticSearch->getQuery();
        $body = $query->toArray();
        if (isset($body['_source'])) {
            unset($body['_source']);
        }
        if (isset($body['sort'])) {
            unset($body['sort']);
        }

        $endpoint = new Count();
        $endpoint->setIndex(\implode(',', $elasticSearch->getIndices()));
        $endpoint->setBody($body);
        $response = $this->client->requestEndpoint($endpoint)->getData();

        if (isset($response['count'])) {
            return \intval($response['count']);
        }
        throw new \RuntimeException('Unexpected count query response structure');
    }

    public function getVersion(): string
    {
        return $this->client->getVersion();
    }

    /**
     * @param string[] $contentTypes
     */
    public function filterByContentTypes(?AbstractQuery $query, array $contentTypes): ?AbstractQuery
    {
        if (0 === \count($contentTypes)) {
            return $query;
        }

        $boolQuery = new BoolQuery();
        if (null !== $query) {
            $boolQuery->addMust($query);
        }
        $boolQuery->setMinimumShouldMatch(1);
        $type = new Terms('_type', $contentTypes);
        $contentType = new Terms(EMSSource::FIELD_CONTENT_TYPE, $contentTypes);
        $boolQuery->addShould($type);
        $boolQuery->addShould($contentType);

        return $boolQuery;
    }

    /**
     * @return string[]
     */
    public function getAliasesFromIndex(string $indexName): array
    {
        return $this->client->getIndex($indexName)->getAliases();
    }

    public function getIndexFromAlias(string $alias): string
    {
        $terms = new TermsAggregation('indexes');
        $terms->setSize(2);
        $terms->setField('_index');
        $esSearch = new ElasticaSearch($this->client);
        $esSearch->setOption(ElasticaSearch::OPTION_SIZE, 0);
        $query = new Query();
        $query->addAggregation($terms);
        $esSearch->setQuery($query);
        $esSearch->addIndex($alias);
        $buckets = $esSearch->search()->getAggregation('indexes')['buckets'] ?? [];

        if (!\is_array($buckets) || 1 !== \count($buckets)) {
            throw new \RuntimeException('Unexpected non-unique or missing index');
        }
        $indexName = $buckets[0]['key'] ?? null;
        if (!\is_string($indexName)) {
            throw new \RuntimeException('Unexpected type for index name');
        }

        return $indexName;
    }

    /**
     * @param string[]     $indexes
     * @param string[]     $contentTypes
     * @param array<mixed> $body
     */
    public function convertElasticsearchBody(array $indexes, array $contentTypes, array $body): Search
    {
        $options = $this->resolveElasticsearchBody($body);
        $queryObject = $this->filterByContentTypes(null, $contentTypes);
        $boolQuery = $this->getBoolQuery();
        $query = $options['query'];
        if (!empty($query) && $queryObject instanceof $boolQuery) {
            $queryObject->addMust($query);
        } elseif (!empty($query)) {
            if (null !== $queryObject) {
                $boolQuery->addMust($queryObject);
            }
            $queryObject = $boolQuery;
            $queryObject->addMust($query);
        }
        $search = new Search($indexes, $queryObject);
        $this->setSearchDefaultOptions($search, $options);
        $search->addAggregations($this->parseAggregations($options['aggs'] ?? []));

        return $search;
    }

    /**
     * @param array<mixed> $param
     */
    public function convertElasticsearchSearch(array $param): Search
    {
        @\trigger_error('This function exists to simplified the migration to elastica, but should not be used on long term', E_USER_DEPRECATED);
        $options = $this->resolveElasticsearchSearchParameters($param);
        $search = $this->convertElasticsearchBody($options['index'], $options['type'], $options['body']);
        $this->setSearchDefaultOptions($search, $options);

        return $search;
    }

    public function getTypeName(string $contentTypeName): string
    {
        $version = $this->getVersion();
        if (\version_compare($version, '7.0') >= 0) {
            return '_doc';
        }
        if (\version_compare($version, '6.0') >= 0) {
            return 'doc';
        }

        return $contentTypeName;
    }

    public function getTypePath(string $contentTypeName): string
    {
        $version = $this->getVersion();
        if (\version_compare($version, '7.0') >= 0) {
            return '.';
        }

        return $this->getTypeName($contentTypeName);
    }

    /**
     * @param string[] $sourceFields
     */
    public function getDocument(string $index, ?string $contentType, string $id, array $sourceFields = []): ElasticsearchDocument
    {
        $contentTypes = [];
        if (null !== $contentType) {
            $contentTypes[] = $contentType;
        }
        $search = $this->generateTermsSearch([$index], '_id', [$id], $contentTypes);
        if (\count($sourceFields) > 0) {
            $search->setSources($sourceFields);
        }
        try {
            return $this->singleSearch($search);
        } catch (NotSingleResultException $e) {
            if (0 === $e->getTotal()) {
                throw new NotFoundException();
            }
            throw $e;
        }
    }

    /**
     * @param string[] $words
     *
     * @return string[]
     */
    public function filterStopWords(string $index, string $analyzer, array $words): array
    {
        $withoutStopWords = [];
        $endpoint = new Analyze();
        $endpoint->setIndex($index);
        foreach ($words as $word) {
            $endpoint->setBody([
                'analyzer' => $analyzer,
                'text' => $word,
            ]);
            $response = $this->client->requestEndpoint($endpoint);
            if (!empty($response->getData()['tokens'] ?? null)) {
                $withoutStopWords[] = $word;
            }
        }

        return $withoutStopWords;
    }

    public function getFieldAnalyzer(string $index, string $field): string
    {
        $endpoint = new GetField();
        $endpoint->setIndex($index);
        $endpoint->setFields($field);

        $response = $this->client->requestEndpoint($endpoint);
        $info = $response->getData();

        $analyzer = 'standard';
        while (\is_array($info = \array_shift($info))) {
            if (isset($info['analyzer'])) {
                $analyzer = $info['analyzer'];
            } elseif (isset($info['mapping'])) {
                $info = $info['mapping'];
            }
        }

        return $analyzer;
    }

    /**
     * @param array<mixed> $parameters
     *
     * @return array{type: string[], index: string[], body: array<mixed>, size: int, from: int, _source: string[], sort: ?array<mixed>}
     */
    private function resolveElasticsearchSearchParameters(array $parameters): array
    {
        $optionResolver = $this->elasticsearchDefaultResolver();
        $optionResolver
            ->setDefaults([
                'type' => null,
                'index' => [],
                'body' => [],
            ])
            ->setAllowedTypes('type', ['string', 'array', 'null'])
            ->setAllowedTypes('index', ['string', 'array'])
            ->setAllowedTypes('body', ['null', 'array', 'string'])
            ->setRequired(['index'])
            ->setNormalizer('type', function (Options $options, $value) {
                if (null === $value) {
                    return [];
                }
                if (!\is_array($value)) {
                    return \explode(',', $value);
                }

                return $value;
            })
            ->setNormalizer('index', function (Options $options, $value) {
                if (!\is_array($value)) {
                    return \explode(',', $value);
                }

                return $value;
            })
            ->setNormalizer('body', function (Options $options, $value) {
                if (\is_string($value)) {
                    $value = \json_decode($value, true);
                }
                if (null === $value) {
                    return [];
                }

                return $value;
            })
        ;
        /** @var array{type: string[], index: string[], body: array<mixed>, size: int, from: int, _source: string[], sort: ?array<mixed>} $resolvedParameters */
        $resolvedParameters = $optionResolver->resolve($parameters);

        return $resolvedParameters;
    }

    /**
     * @param array<mixed> $options
     */
    private function createElasticaSearch(Search $search, array $options): ElasticaSearch
    {
        $boolQuery = $this->filterByContentTypes($search->getQuery(), $search->getContentTypes());
        $query = new Query($boolQuery);
        if (\count($search->getSources())) {
            $query->setSource($search->getSources());
        }
        if (null !== $search->getSort()) {
            $query->setSort($search->getSort());
        }

        $highlightArgs = $search->getHighlight();
        if (null !== $highlightArgs && \count($highlightArgs) > 0) {
            $query->setHighlight($highlightArgs);
        }

        foreach ($search->getAggregations() as $aggregation) {
            $query->addAggregation($aggregation);
        }

        $esSearch = new ElasticaSearch($this->client);
        $esSearch->setQuery($query);
        $esSearch->addIndices($search->getIndices());
        $esSearch->setOptions($options);
        if (null !== $search->getPostFilter()) {
            $query->setPostFilter($search->getPostFilter());
        }
        $suggest = $search->getSuggest();
        if (null !== $suggest && \count($suggest) > 0) {
            $esSearch->setSuggest($suggest);
        }

        return $esSearch;
    }

    /**
     * @param array<mixed> $parameters
     *
     * @return array{aggs: ?array, query: ?array, size: int, from: int, _source: ?string[], sort: ?array}
     */
    private function resolveElasticsearchBody(array $parameters): array
    {
        $resolver = $this->elasticsearchDefaultResolver();
        $resolver
            ->setDefaults([
                'query' => null,
                'aggs' => null,
            ])
            ->setAllowedTypes('query', ['array', 'string', 'null'])
            ->setAllowedTypes('aggs', ['array', 'string', 'null'])
            ->setNormalizer('query', function (Options $options, $value) {
                if (\is_string($value)) {
                    $value = \json_decode($value, true);
                }

                return $value;
            })
            ->setNormalizer('aggs', function (Options $options, $value) {
                if (\is_string($value)) {
                    $value = \json_decode($value, true);
                }

                return $value;
            })
        ;
        /** @var array{aggs: ?array, query: ?array, size: int, from: int, _source: ?string[], sort: ?array} $resolvedParameters */
        $resolvedParameters = $resolver->resolve($parameters);

        return $resolvedParameters;
    }

    /**
     * @param array<mixed> $agg
     */
    private function addAggregation(string $name, array $agg): ElasticaAggregation
    {
        $subAggregations = [];
        if (isset($agg['aggs'])) {
            $subAggregations = $this->parseAggregations($agg['aggs']);
            unset($agg['aggs']);
        }
        if (!\is_array($agg) || 1 !== \count($agg)) {
            throw new \RuntimeException('Unexpected aggregation basename');
        }
        $aggregation = new ElasticaAggregation($name);
        foreach ($agg as $basename => $rule) {
            $aggregation->setConfig($basename, $rule);
            foreach ($subAggregations as $subAggregation) {
                $aggregation->addAggregation($subAggregation);
            }
        }

        return $aggregation;
    }

    /**
     * @param array<mixed> $aggs
     *
     * @return ElasticaAggregation[]
     */
    private function parseAggregations(array $aggs): array
    {
        $aggregations = [];
        foreach ($aggs as $name => $agg) {
            $aggregations[] = $this->addAggregation($name, $agg);
        }

        return $aggregations;
    }

    private function elasticsearchDefaultResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults([
                'size' => 20,
                'from' => 0,
                '_source' => [],
                'sort' => null,
            ])
            ->setAllowedTypes('size', ['int'])
            ->setAllowedTypes('from', ['int'])
            ->setAllowedTypes('_source', ['array', 'string', 'bool'])
            ->setAllowedTypes('sort', ['array', 'null'])
            ->setNormalizer('_source', function (Options $options, $value) {
                if (null === $value || true === $value) {
                    return null;
                }
                if (false === $value) {
                    return [EMSSource::FIELD_CONTENT_TYPE];
                }
                if (!\is_array($value)) {
                    return [$value];
                }

                return $value;
            })
        ;

        return $resolver;
    }

    /**
     * @param array{size: int, from: int, sort: ?array, _source: ?array} $options
     */
    private function setSearchDefaultOptions(Search $search, array $options): void
    {
        $search->setSize($options['size']);
        $search->setFrom($options['from']);
        $sort = $options['sort'];
        if (null !== $sort && !empty($sort)) {
            $search->setSort($sort);
        }
        $sources = $options['_source'];
        if (null !== $sources && !empty($sources)) {
            $search->setSources($sources);
        }
    }
}
