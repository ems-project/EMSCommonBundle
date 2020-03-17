<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Request;

interface RequestInterface
{
    public function getScroll(): string;
    public function setSize(int $size): void;
    public function toArray(): array;
}