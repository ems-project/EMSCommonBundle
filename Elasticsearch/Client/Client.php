<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Client;

use Elasticsearch\Client as ElasticsearchClient;
use EMS\CommonBundle\Contracts\Elasticsearch\ClientInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Cluster;
use Psr\Log\LoggerInterface;

final class Client implements ClientInterface
{
    /** @var ElasticsearchClient */
    private $client;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(ElasticsearchClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function addAlias(string $alias, string $index): void
    {
        $this->client->indices()->updateAliases([
           'body' => [
               'actions' => [
                   'add' => [
                        'index' => $index,
                        'alias' => $alias,
                   ]
               ]
           ]
        ]);
    }

    public function getHealth(): Cluster\HealthInterface
    {
        return new Health($this->client->cluster()->health());
    }

    public function getInfo(): Cluster\InfoInterface
    {
        return new Info($this->client->info());
    }

    public function removeAlias(string $alias, string $index): void
    {
        $this->client->indices()->updateAliases([
            'body' => [
                'actions' => [
                    'remove' => [
                        'index' => $index,
                        'alias' => $alias,
                    ]
                ]
            ]
        ]);
    }

    public function removeIndex(string $index): void
    {
        $this->client->indices()->delete(['index' => $index]);
    }
}
