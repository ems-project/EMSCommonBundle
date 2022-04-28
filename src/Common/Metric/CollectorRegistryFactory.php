<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Metric;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

final class CollectorRegistryFactory
{
    private \Redis $redis;
    private string $redisPrefix;

    public const TYPE_APC = 'apc';
    public const TYPE_IN_MEMORY = 'in_memory';
    public const TYPE_REDIS = 'redis';

    public const TYPES = [
        self::TYPE_APC,
        self::TYPE_IN_MEMORY,
        self::TYPE_REDIS,
    ];

    public function __construct(\Redis $redis, ?string $redisPrefix)
    {
        $this->redis = $redis;
        $this->redisPrefix = $redisPrefix ?? 'EMS_COLLECTOR_';
    }

    public function create(string $type): CollectorRegistry
    {
        $adapter = $this->createAdapter($type);

        return new CollectorRegistry($adapter);
    }

    private function createAdapter(string $type): Adapter
    {
        if (self::TYPE_APC === $type) {
            return new APC();
        }

        if (self::TYPE_REDIS === $type) {
            $adapter = Redis::fromExistingConnection($this->redis);
            $adapter::setPrefix($this->redisPrefix);

            return $adapter;
        }

        return new InMemory();
    }
}
