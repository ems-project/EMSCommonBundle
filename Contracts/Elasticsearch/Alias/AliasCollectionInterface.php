<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch\Alias;

interface AliasCollectionInterface extends \IteratorAggregate, \Countable
{
    public function count(): int;

    /**
     * @return AliasInterface[]
     */
    public function getIterator(): iterable;
}
