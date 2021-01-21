<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch;

use Elasticsearch\ConnectionPool\SniffingConnectionPool;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class ElasticaFactory
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
            $url = \parse_url($host);
            if (false !== $url && 'http' === ($url['scheme'] ?? null)) {
                $servers[] = $url;
            } else {
                $servers[] = ['url' => $host];
            }
        }

        $config = [
            'servers' => $servers,
            'connectionPool' => $connectionPool,
        ];

        $client = new Client($config);

        if ('dev' === $this->env && 'cli' !== \php_sapi_name()) {
            $client->setStopwatch(new Stopwatch());
            $client->setLogger($this->logger);
        }

        return $client;
    }
}
