<?php

namespace EMS\CommonBundle\Elasticsearch\Elastica;

use Elastica\ResultSet as ElasticaResultSet;
use Elastica\Search as ElasticaSearch;

class Scroll extends \Elastica\Scroll
{
    public function next()
    {
        $options = $this->_search->getOptions();
        if (isset($options[ElasticaSearch::OPTION_SIZE])) {
            unset($options[ElasticaSearch::OPTION_SIZE]);
        }
        $this->_search->setOptions($options);
        parent::next();
    }

    // phpcs:disable
    protected function _setScrollId(ElasticaResultSet $resultSet): void
    {
        $newResultSet = new ResultSet($resultSet->getResponse(), $resultSet->getQuery(), $resultSet->getResults());
        parent::_setScrollId($newResultSet);
    }

    // phpcs:disable
}
