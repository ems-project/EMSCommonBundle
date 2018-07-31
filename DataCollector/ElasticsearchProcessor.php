<?php

namespace EMS\CommonBundle\DataCollector;

class ElasticsearchProcessor
{
    /**
     * @var ElasticsearchDataCollector
     */
    private $collector;

    /**
     * @param ElasticsearchDataCollector $collector
     */
    public function __construct(ElasticsearchDataCollector $collector)
    {
        $this->collector = $collector;
    }

    /**
     * @param array $record
     *
     * @return array
     */
    public function processRecord(array $record)
    {
        $this->collector->addData($record);

        return $record;
    }
}