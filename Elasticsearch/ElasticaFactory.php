<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch;

use Elastica\Client;
use Elasticsearch\ConnectionPool\SniffingConnectionPool;
use Psr\Log\LoggerInterface;

final class ElasticaFactory
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

    /**
     * @param array<string> $hosts
     */
    public function fromConfig(array $hosts, string $connectionPool = SniffingConnectionPool::class): Client
    {
        $servers = [];
        foreach ($hosts ?? [] as $host) {
            $servers[] = \parse_url($host);
        }

        $config = [
            'servers' => $servers,
            'connectionPool' => $connectionPool,
        ];

        if ('dev' === $this->env && 'cli' !== \php_sapi_name()) {
            return new Client($config, null, $this->logger);
        }

        return new Client($config);
    }
}
