<?php

namespace EMS\CommonBundle\Common\Admin;

use EMS\CommonBundle\Common\Standard\Hash;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiFactoryInterface;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class AdminHelper
{
    private CoreApiFactoryInterface $coreApiFactory;
    private CacheItemPoolInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        CoreApiFactoryInterface $coreApiFactory,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger
    ) {
        $this->coreApiFactory = $coreApiFactory;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function login(string $baseUrl, string $username, string $password): CoreApiInterface
    {
        $coreApi = $this->coreApiFactory->create($baseUrl);
        $coreApi->authenticate($username, $password);
        $coreApi->setLogger($this->logger);
        $this->cache->save($this->apiCacheBaseUrl()->set($coreApi->getBaseUrl()));
        $this->cache->save($this->apiCacheToken($coreApi)->set($coreApi->getToken()));

        return $coreApi;
    }

    private function apiCacheBaseUrl(): CacheItemInterface
    {
        return $this->cache->getItem('ems_admin_base_url');
    }

    private function apiCacheToken(CoreApiInterface $coreApi): CacheItemInterface
    {
        return $this->cache->getItem(Hash::string($coreApi->getBaseUrl(), 'token_'));
    }

    public function getCoreApi(): CoreApiInterface
    {
        $coreApi = $this->coreApiFactory->create($this->apiCacheBaseUrl()->get());
        $coreApi->setLogger($this->logger);
        $coreApi->setToken($this->apiCacheToken($coreApi)->get());

        return $coreApi;
    }
}
