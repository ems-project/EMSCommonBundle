<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch\Search;

use EMS\CommonBundle\Contracts\Elasticsearch\Document\DocumentCollectionInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Document\DocumentInterface;

interface SearchResponseInterface
{
    public function hasDocuments(): bool;

    /**
     * @return iterable|DocumentInterface[]
     */
    public function getDocuments(): iterable;

    public function getDocumentCollection(): DocumentCollectionInterface;
    public function getScrollId(): ?string;
    public function getTotal(): int;
    public function getTotalDocuments(): int;

    /**
     * @deprecated Please use the other public functions, this toArray() function is for legacy code.
     */
    public function toArray(): array;
}
