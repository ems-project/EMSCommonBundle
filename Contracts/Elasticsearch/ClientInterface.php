<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch;

interface ClientInterface
{
    public function addAlias(string $alias, string $index): void;

    public function getInfo(): Cluster\InfoInterface;
    public function getHealth(): Cluster\HealthInterface;

    public function removeAlias(string $alias, string $index): void;
    public function removeIndex(string $index): void;
}
