<?php

namespace EMS\CommonBundle\Elasticsearch;

use Elasticsearch\ClientBuilder;
use Elasticsearch\ConnectionPool\SniffingConnectionPool;
use Psr\Log\LoggerInterface;

class Factory
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $env;

    /**
     * @param LoggerInterface $logger
     * @param string $env
     */
    public function __construct(LoggerInterface $logger, string $env)
    {
        $this->logger = $logger;
        $this->env = $env;
    }

    /**
     * @param array $config
     *
     * @return \Elasticsearch\Client
     */
    public function fromConfig(array $config)
    {
        if ($this->env === 'dev' && php_sapi_name() !== 'cli') {
            //for performance reason only in dev mode: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#enabling_logger
            $config['Tracer'] = $this->logger;
        }
        $config['connectionPool'] = SniffingConnectionPool::class;

        return ClientBuilder::fromConfig($config);
    }
}