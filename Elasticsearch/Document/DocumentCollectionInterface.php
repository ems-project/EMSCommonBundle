<?php

namespace EMS\CommonBundle\Elasticsearch\Document;

interface DocumentCollectionInterface extends \IteratorAggregate, \Countable
{
    public function add(DocumentInterface $document): void;
    public function count(): int;

    /**
     * @return DocumentInterface[]
     */
    public function getIterator(): \ArrayIterator;
}
