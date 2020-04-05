<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch\Search;

interface SearchRequestInterface
{
    public function addIndex(string $index): SearchRequestInterface;
    public function addContentType(string $contentType): SearchRequestInterface;
    public function addSourceInclude(string $include): SearchRequestInterface;
    public function addSourceExclude(string $exclude): SearchRequestInterface;

    public function disableSource(): SearchRequestInterface;
    public function enableSource(): SearchRequestInterface;

    /**
     * @internal should only be used by the client
     */
    public function getParams(): array;

    public function setBody(array $body): SearchRequestInterface;
    public function setContentTypes(array $contentTypes): SearchRequestInterface;
    public function setIndexes(array $indexes): SearchRequestInterface;
    public function setPage(int $page): SearchRequestInterface;
    public function setSize(int $size): SearchRequestInterface;
    public function setSourceExcludes(array $excludes): SearchRequestInterface;
    public function setSourceIncludes(array $includes): SearchRequestInterface;
    public function setVersion(bool $version): SearchRequestInterface;
}
