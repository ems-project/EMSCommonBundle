<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Client;

use Elasticsearch\Client as ElasticsearchClient;
use EMS\CommonBundle\Contracts\Elasticsearch\Alias\AliasCollectionInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\ClientInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Cluster\HealthInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Cluster\InfoInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Document\DocumentInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Search\SearchRequestInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Search\SearchResponseInterface;
use EMS\CommonBundle\Elasticsearch\Alias\AliasCollection;
use EMS\CommonBundle\Elasticsearch\Cluster\Health;
use EMS\CommonBundle\Elasticsearch\Cluster\Info;
use EMS\CommonBundle\Elasticsearch\Search\Body;
use EMS\CommonBundle\Elasticsearch\Search\SearchRequest;
use EMS\CommonBundle\Elasticsearch\Search\SearchResponse;
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

    public function createSearchRequest(array $indexes = [], array $contentTypes = [], array $body = []): SearchRequestInterface
    {
        return new SearchRequest($indexes, $contentTypes, $body);
    }

    public function getAliases(): AliasCollectionInterface
    {
        return new AliasCollection($this->client->cat()->aliases([]));
    }

    public function getDocument(string $index, string $contentType, string $id): ?DocumentInterface
    {
        $search = $this->searchByContentType($index, $contentType, [
            'query' => [
                ['term' => ['_id' => $id]]
            ]
        ]);

        return $search->getDocumentCollection()->first();
    }

    public function getHealth(): HealthInterface
    {
        return new Health($this->client->cluster()->health());
    }

    public function getInfo(): InfoInterface
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

    public function scroll(string $index, array $body, int $size = 10, string $scroll = '30s'): iterable
    {
        $scrollResponse = new SearchResponse($this->client->search([
            'body' => $body,
            'index' => $index,
            'scroll' => $scroll,
            'size' => $size
        ]));

        return $this->doScroll($scrollResponse, $scroll);
    }

    public function scrollByContentType(string $index, string $contentType, array $body, int $size = 10, string $scroll = '30s'): iterable
    {
        $scrollResponse = new SearchResponse($this->client->search([
            'body' => Body::addContentType($body, $contentType),
            'index' => $index,
            'scroll' => $scroll,
            'size' => $size,
            'version' => true,
        ]));

        return $this->doScroll($scrollResponse, $scroll);
    }

    public function search(string $index, array $body, int $size = 10): SearchResponseInterface
    {
        return new SearchResponse($this->client->search([
            'body' => $body,
            'index' => $index,
            'size' => $size,
            'version' => true,
        ]));
    }

    public function searchByContentType(string $index, string $contentType, array $body, int $size = 10): SearchResponseInterface
    {
        return new SearchResponse($this->client->search([
            'body' => Body::addContentType($body, $contentType),
            'index' => $index,
            'size' => $size,
            'version' => true,
        ]));
    }

    public function searchByRequest(SearchRequestInterface $searchRequest): SearchResponseInterface
    {
        return new SearchResponse($this->client->search($searchRequest->getParams()));
    }

    private function doScroll(SearchResponse $scrollResponse, string $scroll): iterable
    {
        while ($scrollResponse->hasDocuments()) {
            yield $scrollResponse;

            $scrollResponse =  new SearchResponse($this->client->scroll([
                'scroll' => $scroll,
                'scroll_id' => $scrollResponse->getScrollId(),
            ]));
        }
    }
}
