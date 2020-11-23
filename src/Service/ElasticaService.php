<?php

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
use EMS\CommonBundle\Elasticsearch\Document\Document;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;
use EMS\CommonBundle\Elasticsearch\Exception\SingleResultException;
use EMS\CommonBundle\Search\Search;
use Psr\Log\LoggerInterface;

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
            if (null !== $waitForStatus) {
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
        $result = $resultSet->offsetGet(0);
        if (1 !== $resultSet->count() || null === $result) {
            throw new SingleResultException($resultSet->count());
        }

        return Document::fromResult($result);
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

        foreach ($search->getAggregations() as $aggregation) {
            $query->addAggregation($aggregation);
        }

        $esSearch = new ElasticaSearch($this->client);

        $esSearch->setQuery($query);
        $esSearch->addIndices($search->getIndices());
        $esSearch->setOptions($options);

        return $esSearch;
    }
}
