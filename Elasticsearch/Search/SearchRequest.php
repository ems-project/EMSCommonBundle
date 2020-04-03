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

    public function getParams(): array
    {
        return [
            'body' => Body::addContentTypes($this->body, $this->contentTypes),
            'from' => $this->from,
            'index' => $this->indexes,
            'size' => $this->size,
        ];
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
}
