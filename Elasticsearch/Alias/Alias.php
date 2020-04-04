<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Alias;

use EMS\CommonBundle\Contracts\Elasticsearch\Alias\AliasInterface;

final class Alias implements AliasInterface
{
    /** @var string */
    private $name;
    /** @var array */
    private $indexes = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addIndex(string $index): void
    {
        if (!in_array($index, $this->indexes, true)) {
            $this->indexes[] = $index;
        }
    }

    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
