<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Metrics;

use EMS\CommonBundle\Contracts\Metric\EMSMetricsCollectorInterface;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;

class RendererService
{
    private CollectorRegistry $collectionRegistry;

    public function __construct(CollectorRegistry $collectionRegistry)
    {
        $this->collectionRegistry = $collectionRegistry;
    }

    public function render(): string
    {
        return (new RenderTextFormat())->render($this->collectionRegistry->getMetricFamilySamples());
    }

    public function renderResponse(): Response
    {
        return new Response($this->render(), 200, ['Content-type' => RenderTextFormat::MIME_TYPE]);
    }
}
