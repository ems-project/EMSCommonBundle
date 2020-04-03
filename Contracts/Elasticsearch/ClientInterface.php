<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch;

use EMS\CommonBundle\Contracts\Elasticsearch\Cluster\HealthInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Cluster\InfoInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Document\DocumentInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Search\SearchRequestInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Search\SearchResponseInterface;

interface ClientInterface
{
    public function addAlias(string $alias, string $index): void;

    public function createSearchRequest(array $indexes = [], array $contentTypes = [], array $body = []): SearchRequestInterface;

    public function getDocument(string $index, string $contentType, string $id): ?DocumentInterface;
    public function getInfo(): InfoInterface;
    public function getHealth(): HealthInterface;

    public function removeAlias(string $alias, string $index): void;
    public function removeIndex(string $index): void;

    /**
     * @return iterable|SearchResponseInterface[]
     */
    public function scroll(string $index, array $body, int $size = 10, string $scroll = '30s'): iterable;

    /**
     * @return iterable|SearchResponseInterface[]
     */
    public function scrollByContentType(string $index, string $contentType, array $body, int $size = 10, string $scroll = '30s'): iterable;

    public function search(string $index, array $body, int $size = 10): SearchResponseInterface;
    public function searchByContentType(string $index, string $contentType, array $body, int $size = 10): SearchResponseInterface;
    public function searchByRequest(SearchRequestInterface $searchRequest): SearchResponseInterface;
}
