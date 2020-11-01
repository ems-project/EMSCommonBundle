<?php

namespace EMS\CommonBundle\Service;

use Elastica\Client;
use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Terms;
use Elastica\ResultSet;
use Elastica\Search as ElasticaSearch;
use EMS\CommonBundle\Elasticsearch\Document\EMSSource;
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

    public function search(Search $search): ResultSet
    {
        $boolQuery = $this->filterByContentTypes($search->getQuery(), $search->getContentTypes());
        $query = new Query($boolQuery);
        if (\count($search->getSources())) {
            $query->setSource($search->getSources());
        }

        $esSearch = new ElasticaSearch($this->client);
        $esSearch->addIndices($search->getIndices());
        $options = [
            ElasticaSearch::OPTION_SIZE => $search->getSize(),
            ElasticaSearch::OPTION_FROM => $search->getFrom(),
        ];

        return $esSearch->search($boolQuery, $options);
    }

    /**
     * @param string[] $contentTypes
     */
    public function filterByContentTypes(AbstractQuery $query, array $contentTypes): AbstractQuery
    {
        if (\count($contentTypes) === 0) {
            return $query;
        }

        $boolQuery = new BoolQuery();
        $boolQuery->addMust($query);
        $boolQuery->setMinimumShouldMatch(1);
        $type = new Terms('_type', $contentTypes);
        $contentType = new Terms(EMSSource::FIELD_CONTENT_TYPE, $contentTypes);
        $boolQuery->addShould($type);
        $boolQuery->addShould($contentType);

        return $boolQuery;
    }
}
