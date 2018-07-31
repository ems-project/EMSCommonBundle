<?php

namespace EMS\CommonBundle\Elasticsearch;

use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;

class Factory
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $config
     *
     * @return \Elasticsearch\Client
     */
    public function fromConfig(array $config)
    {
        $config['Tracer'] = $this->logger;

        return ClientBuilder::fromConfig($config);
    }
}