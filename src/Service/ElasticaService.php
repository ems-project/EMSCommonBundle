<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service;

use Elastica\Client;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Terms;
use Elastica\Request;
use Elastica\ResultSet;
use Elastica\Scroll;
use Elastica\Search as ElasticaSearch;
use EMS\CommonBundle\Elasticsearch\Aggregation\ElasticaAggregation;
use EMS\CommonBundle\Elasticsearch\Document\Document;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;
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

    public function getHealthStatus(string $waitForStatus = null, string $timeout = '10s'): string
    {
        try {
            $query = [
                'timeout' => $timeout,
            ];
            if ($waitForStatus !== null) {
                $query['wait_for_status'] = $waitForStatus;
            }
            $clusterHealthResponse = $this->client->request('_cluster/health', Request::GET, [], $query);

            return $clusterHealthResponse->getData()['status'] ?? 'red';
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return 'red';
        }
    }

    public function singleSearch(Search $search): Document
    {
        $resultSet = $this->search($search);
        if ($resultSet->count() === 0) {
            throw new NotSingleResultException(0);
        }
        $result = $resultSet->offsetGet(0);
        if ($resultSet->count() !== 1 || $result === null) {
            throw new NotSingleResultException($resultSet->count());
        }
        return Document::fromResult($result);
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
        return $this->createElasticaSearch($search, $search->getScrollOptions())->scroll($expiryTime);
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
        if (\count($contentTypes) === 0) {
            return $query;
        }

        $boolQuery = new BoolQuery();
        if ($query !== null) {
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


    /**
     * @param string[] $indexes
     * @param string[] $contentTypes
     * @param array<mixed> $body
     * @return Search
     */
    public function convertElasticsearchBody(array $indexes, array $contentTypes, array $body): Search
    {
        $options = $this->resolveElasticsearchBody($body);
        $queryObject = $this->filterByContentTypes(null, $contentTypes);
        $boolQuery = $this->getBoolQuery();
        $query =  $options['query'];
        if (!empty($query) && $queryObject instanceof $boolQuery) {
            $queryObject->addMust($query);
        } elseif (!empty($query)) {
            if ($queryObject !== null) {
                $boolQuery->addMust($queryObject);
            }
            $queryObject = $boolQuery;
            $queryObject->addMust($query);
        }
        $search = new Search($indexes, $queryObject);
        $aggs =  $options['aggs'];
        if ($aggs === null) {
            return $search;
        }
        foreach ($aggs as $name => $agg) {
            if (!\is_array($agg) || \count($agg) !== 1) {
                throw new \RuntimeException('Unexpected aggregation basename');
            }
            foreach ($agg as $basename => $rule) {
                $aggregation = new ElasticaAggregation($name);
                $aggregation->setConfig($basename, $rule);
                $search->addAggregation($aggregation);
            }
        }
        return $search;
    }

        /**
     * @param array<mixed> $param
     * @return Search
     */
    public function convertElasticsearchSearch(array $param): Search
    {
        @trigger_error("This function exists to simplified the migration to elastica, but should not be used on long term", E_USER_DEPRECATED);
        $options = $this->resolveElasticsearchSearchParameters($param);

        $search = $this->convertElasticsearchBody($options['index'], $options['type'], $options['body']);

        $search->setSize($options['size']);
        $search->setFrom($options['from']);
        $sort = $options['sort'];
        if ($sort !== null) {
            $search->setSort($sort);
        }
        $sources = $options['_source'];
        if ($sources !== null) {
            $search->setSources($sources);
        }
        return $search;
    }

    /**
     * @param array<mixed> $parameters
     * @return array{type: string[], index: string[], body: array<mixed>, size: int, from: int, _source: string[], sort: array<mixed>}
     */
    private function resolveElasticsearchSearchParameters(array $parameters): array
    {
        $optionResolver = new OptionsResolver();
        $optionResolver
            ->setDefaults([
                'type' => null,
                'index' => [],
                'body' => [],
                'size' => 20,
                'from' => 0,
                '_source' => [],
                'sort' => []
            ])
            ->setAllowedTypes('type', ['string', 'array', 'null'])
            ->setAllowedTypes('index', ['string', 'array'])
            ->setAllowedTypes('body', ['null', 'array', 'string'])
            ->setAllowedTypes('size', ['int'])
            ->setAllowedTypes('from', ['int'])
            ->setAllowedTypes('_source', ['array', 'string', 'bool'])
            ->setAllowedTypes('sort', ['array'])
            ->setRequired(['index'])
            ->setNormalizer('type', function (Options $options, $value) {
                if ($value === null) {
                    return [];
                }
                if (!\is_array($value)) {
                    return [$value];
                }
                return $value;
            })
            ->setNormalizer('index', function (Options $options, $value) {
                if (!\is_array($value)) {
                    return [$value];
                }
                return $value;
            })
            ->setNormalizer('body', function (Options $options, $value) {
                if (\is_string($value)) {
                    $value = \json_decode($value, true);
                }
                if ($value === null) {
                    return [];
                }
                return $value;
            })
            ->setNormalizer('_source', function (Options $options, $value) {
                if ($value === true) {
                    return [];
                }
                if ($value === false) {
                    return [EMSSource::FIELD_CONTENT_TYPE];
                }
                if (!\is_array($value)) {
                    return [$value];
                }
                return $value;
            })
        ;
        /** @var array{type: string[], index: string[], body: array{query: array, aggs: array}, size: int, from: int, _source: string[], sort: array<mixed>} $resolvedParameters */
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
        if ($search->getSort() !== null) {
            $query->setSort($search->getSort());
        }

        foreach ($search->getAggregations() as $aggregation) {
            $query->addAggregation($aggregation);
        }

        $esSearch = new ElasticaSearch($this->client);

        $esSearch->setQuery($query);
        $esSearch->addIndices($search->getIndices());
        $esSearch->setOptions($options);
        return $esSearch;
    }


    /**
     * @param array<mixed> $parameters
     * @return array{aggs: ?array, query: ?array}
     */
    private function resolveElasticsearchBody(array $parameters): array
    {
        $resolver = new OptionsResolver();
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
        /** @var array{aggs: ?array, query: ?array} $resolvedParameters */
        $resolvedParameters = $resolver->resolve($parameters);
        return $resolvedParameters;
    }
}
