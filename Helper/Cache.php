<?php

namespace EMS\CommonBundle\Helper;

use Symfony\Component\HttpFoundation\Response;

class Cache
{
    /** @var string */
    private $hashAlgo;

    public function __construct(string $hashAlgo)
    {
        $this->hashAlgo = $hashAlgo;
    }

    public function generateEtagFromResponse(Response $response, ?\DateTime $lastUpdateDate, bool $immutableRoute): void
    {
        if (!is_string($response->getContent())) {
            return;
        }

        $etag = \hash($this->hashAlgo, $response->getContent());
        $this->makeResponseCacheable($response, $etag, $lastUpdateDate, $immutableRoute);
    }

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

        if ($lastUpdateDate !== null) {
            $response->setLastModified($lastUpdateDate);
        }
    }
}
