<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class ElasticaLogger extends AbstractLogger
{
    protected ?LoggerInterface $logger;
    /** @var array<mixed> */
    protected $queries = [];
    protected bool $debug;

    public function __construct(?LoggerInterface $logger = null, bool $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * @param array<mixed>|string $data       Arguments
     * @param array<mixed>        $connection Host, port, transport, and headers of the query
     * @param array<mixed>        $query      Arguments
     */
    public function logQuery(string $path, string $method, $data, float $queryTime, array $connection = [], array $query = [], int $engineTime = 0, int $itemCount = 0): void
    {
        $executionMS = $queryTime * 1000;

        if ($this->debug) {
            $e = new \Exception();
            if (\is_string($data)) {
                $jsonStrings = \explode("\n", $data);
                $data = [];
                foreach ($jsonStrings as $json) {
                    if ('' != $json) {
                        $data[] = \json_decode($json, true);
                    }
                }
            } else {
                $data = [$data];
            }

            $this->queries[] = [
                'path' => $path,
                'method' => $method,
                'data' => $data,
                'executionMS' => $executionMS,
                'engineMS' => $engineTime,
                'connection' => $connection,
                'queryString' => $query,
                'itemCount' => $itemCount,
                'backtrace' => $e->getTraceAsString(),
            ];
        }

        if (null !== $this->logger) {
            $message = \sprintf('%s (%s) %0.2f ms', $path, $method, $executionMS);
            $this->logger->info($message, (array) $data);
        }
    }

    public function getNbQueries(): int
    {
        return \count($this->queries);
    }

    /**
     * @return array<mixed>
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    public function log($level, $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

    public function reset(): void
    {
        $this->queries = [];
    }
}
