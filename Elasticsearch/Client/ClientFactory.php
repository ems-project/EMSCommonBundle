<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Client;

use Elasticsearch\ClientBuilder;
use Elasticsearch\ConnectionPool\SniffingConnectionPool;
use Psr\Log\LoggerInterface;

final class ClientFactory
{
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $env;

    public function __construct(LoggerInterface $logger, string $env)
    {
        $this->logger = $logger;
        $this->env = $env;
    }

    public function fromConfig(array $config): Client
    {
        if ($this->env === 'dev' && php_sapi_name() !== 'cli') {
            //for performance reason only in dev mode: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#enabling_logger
            $config['Tracer'] = $this->logger;
        }
        $config['connectionPool'] = SniffingConnectionPool::class;

        $elasticsearchClient = ClientBuilder::fromConfig($config);

        return new Client($elasticsearchClient, $this->logger);
    }
}
