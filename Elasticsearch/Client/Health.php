<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Client;

use EMS\CommonBundle\Contracts\Elasticsearch\Cluster\HealthInterface;

final class Health implements HealthInterface
{
    /** @var int */
    private $activePrimaryShards;
    /** @var int */
    private $activeShards;
    /** @var float */
    private $activeShardsPercentAsNumber;
    /** @var int */
    private $delayedUnassignedShards;
    /** @var int */
    private $initializingShards;
    /** @var int */
    private $numberOfDataNodes;
    /** @var int */
    private $numberOfInFlightFetch;
    /** @var int */
    private $numberOfNodes;
    /** @var int */
    private $numberOfPendingTasks;
    /** @var int */
    private $relocatingShards;
    /** @var string */
    private $status;
    /** @var int */
    private $taskMaxWaitingInQueueMillis;
    /** @var int */
    private $unassignedShards;
    /** @var int */

    public function __construct(array $status)
    {
        $this->activePrimaryShards = (int) $status['active_primary_shards'];
        $this->activeShards = (int) $status['active_shards'];
        $this->activeShardsPercentAsNumber = (float) $status['active_shards_percent_as_number'];
        $this->delayedUnassignedShards = (int) $status['delayed_unassigned_shards'];
        $this->initializingShards = (int) $status['initializing_shards'];
        $this->numberOfDataNodes = (int) $status['number_of_data_nodes'];
        $this->numberOfInFlightFetch = (int) $status['number_of_in_flight_fetch'];
        $this->numberOfNodes = (int) $status['number_of_nodes'];
        $this->numberOfPendingTasks = (int) $status['number_of_pending_tasks'];
        $this->relocatingShards = (int) $status['relocating_shards'];
        $this->status = $status['status'];
        $this->taskMaxWaitingInQueueMillis = (int) $status['task_max_waiting_in_queue_millis'];
        $this->unassignedShards = (int) $status['unassigned_shards'];
    }

    public function getActivePrimaryShards(): int
    {
        return $this->activePrimaryShards;
    }

    public function getActiveShards(): int
    {
        return $this->activeShards;
    }

    public function getActiveShardsPercentAsNumber(): float
    {
        return $this->activeShardsPercentAsNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDelayedUnassignedShards(): int
    {
        return $this->delayedUnassignedShards;
    }

    public function getInitializingShards(): int
    {
        return $this->initializingShards;
    }

    public function getNumberOfDataNodes(): int
    {
        return $this->numberOfDataNodes;
    }

    public function getNumberOfInFlightFetch(): int
    {
        return $this->numberOfInFlightFetch;
    }

    public function getNumberOfNodes(): int
    {
        return $this->numberOfNodes;
    }

    public function getNumberOfPendingTasks(): int
    {
        return $this->numberOfPendingTasks;
    }

    public function getRelocatingShards(): int
    {
        return $this->relocatingShards;
    }

    public function getTaskMaxWaitingInQueueMillis(): int
    {
        return $this->taskMaxWaitingInQueueMillis;
    }

    public function getUnassignedShards(): int
    {
        return $this->unassignedShards;
    }

    public function isGreen(): bool
    {
        return 'green' === $this->status;
    }
}
