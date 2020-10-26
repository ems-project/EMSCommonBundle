<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Request;

final class Request implements RequestInterface
{
    /** @var array */
    private $body;
    /** @var string */
    private $index;
    /** @var string */
    private $scroll = '30s';
    /** @var int */
    private $size = 10;

    public function __construct(string $index, array $body)
    {
        $this->index = $index;
        $this->body = $body;
    }

    public function getScroll(): string
    {
        return $this->scroll;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function toArray(): array
    {
        return [
            'body' => $this->body,
            'index' => $this->index,
            'scroll' => $this->scroll,
            'size' => $this->size,
        ];
    }
}
