<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Metric;

use EMS\CommonBundle\Common\Standard\DateTime;
use Prometheus\CollectorRegistry;
use Prometheus\MetricFamilySamples;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class MetricCollector
{
    private CollectorRegistryFactory $collectorRegistryFactory;
    private CacheItemPoolInterface $cache;
    private ?CollectorRegistry $collectorRegistry = null;
    private string $collectorRegistryType;

    /** @var iterable<MetricCollectorInterface> */
    private iterable $collectors;

    public function __construct(
        CollectorRegistryFactory $collectorRegistryFactory,
        CacheItemPoolInterface $cache,
        string $collectorRegistryType,
        iterable $collectors
    ) {
        $this->collectorRegistryFactory = $collectorRegistryFactory;
        $this->collectorRegistryType = $collectorRegistryType;
        $this->cache = $cache;
        $this->collectors = $collectors;
    }

    /**
     * @return MetricFamilySamples[]
     */
    public function getMetrics(): array
    {
        if ($this->collectorRegistryType === CollectorRegistryFactory::TYPE_IN_MEMORY) {
            $this->collect();
        }

        return $this->getCollectorRegistry()->getMetricFamilySamples();
    }

    private function getCache(): CacheItemInterface
    {
        return $this->cache->getItem('metrics');
    }

    private function getValidity(): array
    {
        $item = $this->getCache();

        return $item->isHit() ? $item->get() : [];
    }

    public function saveValidity(array $validity): void
    {
        $item = $this->getCache();
        $item->set($validity);

        $this->cache->save($item);
    }

    public function collect()
    {
        $collectorRegistry = $this->getCollectorRegistry();
        $now = DateTime::create('now')->getTimestamp();
        $validity = $this->getValidity();

        foreach ($this->collectors as $collector) {
            $validUntil = $validity[$collector->getName()] ?? null;
            if (null !== $validUntil && $validUntil > $now) {
                continue;
            }

            $collector->collect($collectorRegistry);
            $validity[$collector->getName()] = $collector->validUntil();
        }

        $this->saveValidity($validity);
    }

    private function getCollectorRegistry(): CollectorRegistry
    {
        if (null === $this->collectorRegistry) {
            $this->collectorRegistry = $this->collectorRegistryFactory->create($this->collectorRegistryType);
        }

        return $this->collectorRegistry;
    }
}