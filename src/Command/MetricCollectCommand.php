<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Command;

use EMS\CommonBundle\Common\Command\AbstractCommand;
use EMS\CommonBundle\Common\Metric\MetricCollector;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class MetricCollectCommand extends AbstractCommand
{
    private MetricCollector $metricCollector;

    public function __construct(MetricCollector $metricCollector)
    {
        parent::__construct();
        $this->metricCollector = $metricCollector;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('EMS - Metric - Collect');

        $this->metricCollector->collect();

        return self::EXECUTE_SUCCESS;
    }
}
