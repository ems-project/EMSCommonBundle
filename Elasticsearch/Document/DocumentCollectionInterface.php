<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

interface DocumentCollectionInterface extends \IteratorAggregate, \Countable
{
    public function count(): int;

    /**
     * @return DocumentInterface[]
     */
    public function getIterator(): iterable;
}
