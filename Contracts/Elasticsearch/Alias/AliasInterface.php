<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch\Alias;

interface AliasInterface
{
    public function getIndexes(): array;
    public function getName(): string;
}
