<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch;

interface ClientInterface
{
    public function getInfo(): Cluster\InfoInterface;
    public function getHealth(): Cluster\HealthInterface;
}
