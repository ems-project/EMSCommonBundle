<?php

namespace EMS\CommonBundle\Elasticsearch\Elastica;

use Elastica\Response as ElasticaResponse;
use Elastica\ResultSet;
use EMS\CommonBundle\Elasticsearch\Response\Response;

class Scroll extends \Elastica\Scroll
{
    // phpcs:disable
    protected function _setScrollId(ResultSet $resultSet): void
    {
        $response = Response::fromResultSet($resultSet);
        $data = $resultSet->getResponse()->getData();
        $data['hits']['total'] = $response->getTotal();
        $elasticaResponse = new ElasticaResponse($data, $resultSet->getResponse()->getStatus());
        $newResultSet = new ResultSet($elasticaResponse, $resultSet->getQuery(), $resultSet->getResults());

        parent::_setScrollId($newResultSet);
    }

    // phpcs:disable
}
