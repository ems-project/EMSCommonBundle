<?php

namespace EMS\CommonBundle\Elasticsearch\Document;

interface DocumentCollectionInterface extends \IteratorAggregate, \Countable
{
    public function count(): int;

    /**
     * @return \ArrayIterator|DocumentInterface[]
     */
    public function getIterator(): \ArrayIterator;
}
