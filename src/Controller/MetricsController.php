<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Controller;

use EMS\CommonBundle\Common\Metric\MetricRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MetricsController extends AbstractController
{
    private MetricRenderer $renderer;

    public function __construct(MetricRenderer $metricsRenderer)
    {
        $this->renderer = $metricsRenderer;
    }

    public function prometheus(): Response
    {
        return $this->renderer->renderResponse();
    }
}
