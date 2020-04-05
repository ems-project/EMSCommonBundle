<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Alias;

use EMS\CommonBundle\Contracts\Elasticsearch\Alias\AliasCollectionInterface;

final class AliasCollection implements AliasCollectionInterface
{
    /** @var Alias[] */
    private $aliases = [];

    public function __construct(array $response)
    {
        foreach ($response as $info) {
            $alias = $this->addAlias($info['alias']);
            $alias->addIndex($info['index']);
        }
    }

    public function count(): int
    {
        return count($this->aliases);
    }

    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->aliases);
    }

    private function addAlias(string $name): Alias
    {
        if (!isset($this->aliases[$name])) {
            $this->aliases[$name] = new Alias($name);
        }

        return $this->aliases[$name];
    }
}
