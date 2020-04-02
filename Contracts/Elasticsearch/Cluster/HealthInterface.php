<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch\Cluster;

interface HealthInterface
{
    public function getActivePrimaryShards(): int;
    public function getActiveShards(): int;
    public function getActiveShardsPercentAsNumber(): float;
    public function getDelayedUnassignedShards(): int;
    public function getInitializingShards(): int;
    public function getNumberOfDataNodes(): int;
    public function getNumberOfInFlightFetch(): int;
    public function getNumberOfNodes(): int;
    public function getNumberOfPendingTasks(): int;
    public function getRelocatingShards(): int;
    public function getStatus(): string;
    public function getTaskMaxWaitingInQueueMillis(): int;
    public function getUnassignedShards(): int;

    public function isGreen(): bool;
}
