<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Metric;

use Prometheus\CollectorRegistry;

interface EMSMetricsCollectorInterface
{
    public function collect(CollectorRegistry $registry): void;
}
