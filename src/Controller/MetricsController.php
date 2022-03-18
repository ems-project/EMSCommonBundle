<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Controller;

use EMS\CommonBundle\Service\Metrics\RendererService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class MetricsController extends AbstractController
{
    private RendererService $renderer;

    public function __construct(RendererService $metricsRenderer)
    {
        $this->renderer = $metricsRenderer;
    }

    public function prometheus(): Response
    {
        return $this->renderer->renderResponse();
    }
}
