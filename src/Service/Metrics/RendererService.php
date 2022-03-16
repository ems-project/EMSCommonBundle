<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Service\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renderer class provides a functionality to render the metrics for the given collector registry.
 */
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
