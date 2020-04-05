<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch\Document;

interface DocumentCollectionInterface extends \IteratorAggregate, \Countable
{
    public function count(): int;
    public function getIds(): array;
    public function first(): ?DocumentInterface;

    /**
     * @return DocumentInterface[]
     */
    public function getIterator(): iterable;
}
