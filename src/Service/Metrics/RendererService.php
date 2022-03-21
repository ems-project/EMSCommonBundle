<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Metrics;

use EMS\CommonBundle\Common\Metric\MetricRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;

class RendererService
{
    private MetricRegistry $metricRegistry;

    public function __construct(MetricRegistry $metricRegistry)
    {
        $this->metricRegistry = $metricRegistry;
    }

    public function render(): string
    {
        return (new RenderTextFormat())->render($this->metricRegistry->getRegistry()->getMetricFamilySamples());
    }

    public function renderResponse(): Response
    {
        return new Response($this->render(), 200, ['Content-type' => RenderTextFormat::MIME_TYPE]);
    }
}
