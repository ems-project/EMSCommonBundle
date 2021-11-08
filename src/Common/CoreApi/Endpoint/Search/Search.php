<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi\Endpoint\Search;

use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Common\CoreApi\Search\Scroll;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Search\SearchInterface;
use EMS\CommonBundle\Search\Search as SearchObject;

class Search implements SearchInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return array<mixed>
     */
    public function search(SearchObject $search): array
    {
        return $this->client->post('/api/search/search', ['search' => $search->serialize()])->getData();
    }

    public function count(SearchObject $search): int
    {
        $count = $this->client->post('/api/search/count', ['search' => $search->serialize()])->getData()['count'] ?? null;
        if (!\is_int($count)) {
            throw new \RuntimeException('Unexpected: count must be a string');
        }

        return $count;
    }

    public function scroll(SearchObject $search, string $expireTime = '3m'): Scroll
    {
        return new Scroll($this->client, $search, $expireTime);
    }

    public function version(): string
    {
        $version = $this->client->get('/api/search/version')->getData()['version'] ?? null;
        if (!\is_string($version)) {
            throw new \RuntimeException('Unexpected: search must be a string');
        }

        return $version;
    }

    public function healthStatus(): string
    {
        $status = $this->client->get('/api/search/health-status')->getData()['status'] ?? null;
        if (!\is_string($status)) {
            throw new \RuntimeException('Unexpected: status must be a string');
        }

        return $status;
    }

    public function refresh(?string $index = null): bool
    {
        $success = $this->client->post('/api/search/refresh', [
            'index' => $index,
        ])->getData()['success'] ?? null;
        if (!\is_bool($success)) {
            throw new \RuntimeException('Unexpected: search must be a boolean');
        }

        return $success;
    }

    /**
     * @return string[]
     */
    public function getIndicesFromAlias(string $alias): array
    {
        $indices = $this->client->post('/api/search/indices-from-alias', [
            'alias' => $alias,
        ])->getData()['indices'] ?? null;
        if (!\is_array($indices)) {
            throw new \RuntimeException('Unexpected: search must be an array');
        }

        return $indices;
    }

    /**
     * @return string[]
     */
    public function getAliasesFromIndex(string $index): array
    {
        $aliases = $this->client->post('/api/search/aliases-from-index', [
            'index' => $index,
        ])->getData()['aliases'] ?? null;
        if (!\is_array($aliases)) {
            throw new \RuntimeException('Unexpected: aliases must be an array');
        }

        return $aliases;
    }

    /**
     * @param string[] $sourceIncludes
     * @param string[] $sourcesExcludes
     *
     * @return array<mixed>
     */
    public function getDocument(string $index, ?string $contentType, string $id, array $sourceIncludes = [], array $sourcesExcludes = []): array
    {
        return $this->client->post('/api/search/document', [
            'index' => $index,
            'content-type' => $contentType,
            'ouuid' => $id,
            'source-includes' => $sourceIncludes,
            'sources-excludes' => $sourcesExcludes,
        ])->getData();
    }
}
