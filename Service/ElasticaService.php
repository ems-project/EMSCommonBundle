<?php

namespace EMS\CommonBundle\Service;

use Elastica\Client;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\Search;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;
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

    /**
     * @param string[] $aliases
     * @param string[] $contentTypes
     * @param array{from?: int, size?: int, scroll?: string, scroll_id?: string } $options
     */
    public function search(array $aliases, array $contentTypes, Query\AbstractQuery $query, array $options = []): ResultSet
    {
        $type = new Query\Terms('_type', $contentTypes);
        $contentType = new Query\Terms(EMSSource::FIELD_CONTENT_TYPE, $contentTypes);

        $boolQuery = new Query\BoolQuery();
        $boolQuery->addMust($query);
        $boolQuery->setMinimumShouldMatch(1);
        $boolQuery->addShould($type);
        $boolQuery->addShould($contentType);

        $esSearch = new Search($this->client);
        $esSearch->addIndices($aliases);
        return $esSearch->search($boolQuery, $options);
    }
}
