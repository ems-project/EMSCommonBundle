<?php

namespace EMS\CommonBundle\Common\CoreApi\Search;

use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Elasticsearch\Document\Document;
use EMS\CommonBundle\Elasticsearch\Document\DocumentInterface;
use EMS\CommonBundle\Search\Search;

/**
 * @implements \Iterator<string, DocumentInterface>
 */
class Scroll implements \Iterator
{
    private Client $client;
    private Search $search;
    private string $expireTime;
    private ?string $nextScrollId;
    private int $currentPage;
    private int $totalPages;
    /**
     * @var mixed[]
     */
    private array $currentResultSet;
    private int $count;
    private int $index = 0;

    public function __construct(Client $client, Search $search, string $expireTime = '3m')
    {
        $this->client = $client;
        $this->search = $search;
        $this->expireTime = $expireTime;
    }

    public function current(): DocumentInterface
    {
        return Document::fromArray($this->currentResultSet['hits']['hits'][$this->index]);
    }

    public function next()
    {
        ++$this->index;
        if (!isset($this->currentResultSet['hits']['hits'][$this->index])) {
            $this->nextScroll();
        }
    }

    private function nextScroll(): void
    {
        $this->currentResultSet = $this->client->post('/api/search/next-scroll', [
            'scroll-id' => $this->nextScrollId,
            'expire-time' => $this->expireTime,
        ])->getData();

        ++$this->currentPage;
        $this->setScrollId();
    }

    private function setScrollId(): void
    {
        $this->index = 0;
        $count = $this->currentResultSet['hits']['total']['value'] ?? $this->currentResultSet['hits']['total'] ?? 0;
        $this->totalPages = \intval($this->search->getSize() > 0 ? \floor($count / $this->search->getSize()) : 0);
        $this->nextScrollId = $this->currentPage <= $this->totalPages ? $this->currentResultSet['_scroll_id'] ?? null : null;
    }

    public function key(): string
    {
        if (null === $this->nextScrollId) {
            throw new \RuntimeException('Invalid scroll');
        }

        return $this->currentResultSet['hits']['hits'][$this->index]['_id'];
    }

    public function valid(): bool
    {
        return null !== $this->nextScrollId;
    }

    public function rewind(): void
    {
        $this->initScroll();
    }

    private function initScroll(): void
    {
        $this->currentResultSet = $this->client->post('/api/search/init-scroll', [
            'search' => $this->search->serialize(),
            'expire-time' => $this->expireTime,
        ])->getData();
        $this->currentPage = 0;
        $this->setScrollId();
    }
}
