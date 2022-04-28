<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Controller;

use EMS\CommonBundle\Common\Metric\MetricCollector;
use Prometheus\RenderTextFormat;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class MetricController extends AbstractController
{
    private MetricCollector $metricCollector;

    public const METRICS = 'ems.controller.metric::metrics';

    public function __construct(MetricCollector $metricCollector)
    {
        $this->metricCollector = $metricCollector;
    }

    public function metrics(): Response
    {
        $metrics = $this->metricCollector->getMetrics();

        $renderFormat = new RenderTextFormat();
        $content = $renderFormat->render($metrics);

        return new Response($content, Response::HTTP_OK, ['Content-type' => RenderTextFormat::MIME_TYPE]);
    }
}
