<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Metric;

class MetricRenderer
{
    private MetricRegistry $registry;

    public function __construct(MetricRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function render(): void
    {
        $registry = $this->registry->getRegistry();
    }

}