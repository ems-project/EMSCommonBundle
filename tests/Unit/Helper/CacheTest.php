<?php

namespace EMS\CommonBundle\Tests\Unit\Helper\Text;

use EMS\CommonBundle\Helper\Cache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class CacheTest extends TestCase
{
    /** @var Response */
    private $mockResponse;

    protected function setUp(): void
    {
        $mockResponse = $this->createMock(Response::class);
        $this->mockResponse = $mockResponse;
    }

    public function testGenerateEtag(): void
    {
        $cache = $this->cache->generateEtag($this->mockResponse);
        self::assertContainsOnly('string',$cache);
    }

    public function testmakeResponseCacheable(): void
    {
        $cacheAble = $this->cache->makeResponseCacheable($this->mockResponse, $$this->cache->generateEtag($this->mockResponse));

        self::assertFalse($cacheAble->setEtag('not'),$cacheAble->getEtag());
        self::assertTrue($cacheAble->setEtag('etag'),$cacheAble->getEtag());

        self::assertFalse($cacheAble->setMaxAge(10),$cacheAble->getMaxAge());
        self::assertTrue($cacheAble->setMaxAge(),$cacheAble->getMaxAge());
    }

    /*     
    public function makeResponseCacheable(Response $response, string $etag, ?\DateTime $lastUpdateDate, bool $immutableRoute): void
    {
        $response->setCache([
            'etag' => $etag,
            'max_age' => $immutableRoute ? 604800 : 600,
            's_maxage' => $immutableRoute ? 2678400 : 3600,
            'public' => true,
            'private' => false,
            'immutable' => $immutableRoute,
        ]);

        if (null !== $lastUpdateDate) {
            $response->setLastModified($lastUpdateDate);
        }
    } 
    */


}