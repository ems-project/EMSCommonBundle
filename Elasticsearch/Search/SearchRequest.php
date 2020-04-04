<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Search;

use EMS\CommonBundle\Contracts\Elasticsearch\Search\SearchRequestInterface;

final class SearchRequest implements SearchRequestInterface
{
    /** @var array */
    private $body;
    /** @var array */
    private $contentTypes;
    /** @var int */
    private $from = 0;
    /** @var array */
    private $indexes;
    /** @var int */
    private $size = 10;
    /** @var array */
    private $sourceIncludes = [];
    /** @var array */
    private $sourceExcludes = [];

    public function __construct(array $indexes = [], array $contentTypes = [], array $body = [])
    {
        $this->body = $body;
        $this->contentTypes = $contentTypes;
        $this->indexes = $indexes;
    }

    public function addContentType(string $contentType): SearchRequestInterface
    {
        if (!in_array($contentType, $this->contentTypes, true)) {
            $this->contentTypes[] = $contentType;
        }

        return $this;
    }

    public function addIndex(string $index): SearchRequestInterface
    {
        if (!in_array($index, $this->indexes, true)) {
            $this->indexes[] = $index;
        }

        return $this;
    }

    public function addSourceInclude(string $include): SearchRequestInterface
    {
        if (!in_array($include, $this->sourceIncludes, true)) {
            $this->sourceIncludes[] = $include;
        }

        return $this;
    }

    public function addSourceExclude(string $exclude): SearchRequestInterface
    {
        if (!in_array($exclude, $this->sourceExcludes, true)) {
            $this->sourceExcludes[] = $exclude;
        }

        return $this;
    }

    public function getParams(): array
    {
        return array_filter([
            'body' => Body::addContentTypes($this->body, $this->contentTypes),
            'from' => $this->from,
            'index' => $this->indexes,
            'size' => $this->size,
            '_source_include' => $this->sourceIncludes,
            '_source_exclude' => $this->sourceExcludes,
        ]);
    }

    public function setBody(array $body): SearchRequestInterface
    {
        $this->body = $body;

        return $this;
    }

    public function setContentTypes(array $contentTypes): SearchRequestInterface
    {
        $this->contentTypes = array_unique($contentTypes);

        return $this;
    }

    public function setIndexes(array $indexes): SearchRequestInterface
    {
        $this->indexes = array_unique($indexes);

        return $this;
    }

    public function setPage(int $page): SearchRequestInterface
    {
        $this->from = ($page - 1) * $this->size;

        return $this;
    }

    public function setSize(int $size): SearchRequestInterface
    {
        $this->size = $size;

        return $this;
    }

    public function setSourceIncludes(array $includes): SearchRequestInterface
    {
        $this->sourceIncludes = $includes;

        return $this;
    }

    public function setSourceExcludes(array $excludes): SearchRequestInterface
    {
        $this->sourceExcludes = $excludes;

        return $this;
    }
}
