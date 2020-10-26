<?php

declare(strict_types=1);

namespace EMS\CommonBundle\DataCollector;

use Psr\Log\LoggerInterface;

final class ElasticsearchProcessor
{
    /** @var ElasticsearchDataCollector */
    private $collector;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(ElasticsearchDataCollector $collector, LoggerInterface $logger)
    {
        $this->collector = $collector;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function __invoke(array $record)
    {
        if (isset($record['context']['response']['_shards']['failures'])) {
            $this->logger->error('{total} failures', [
                'total' => $record['context']['response']['_shards']['failed'],
                'failures' => $this->getFailures($record['context']['response']['_shards']['failures']),
                'record' => $record,
            ]);
        }

        $this->collector->addData($record);

        return $record;
    }

    private function getFailures(array $response): array
    {
        $failures = [];

        foreach ($response as $failure) {
            $failures[] = \sprintf('%s on %s', $failure['reason']['reason'], $failure['index']);
        }

        return $failures;
    }
}
