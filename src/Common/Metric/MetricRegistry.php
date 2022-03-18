<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Metric;

use EMS\CommonBundle\Contracts\Metric\EMSMetricsCollectorInterface;
use Prometheus\CollectorRegistry;

class MetricRegistry
{
    /**
     * @var EMSMetricsCollectorInterface[]
     */
    private array $collectors;

    private CollectorRegistry $collectorRegistry;

    /**
     * @param array<string, mixed> $collectors
     */
    public function __construct(array $collectors, CollectorRegistry $collectorRegistry)
    {
        $this->collectors = $collectors;
        $this->collectorRegistry = $collectorRegistry;
    }

    public function getRegistry(): CollectorRegistry
    {
        foreach ($this->collectors as $collector) {
            $collector->collect($this->collectorRegistry);
        }

        return $this->collectorRegistry;
    }

    /**
     * @return EMSMetricsCollectorInterface[]
     */
    public function getCollectors(): array
    {
        return $this->collectors;
    }
}
