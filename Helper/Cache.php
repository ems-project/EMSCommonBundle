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

    public function generateEtagFromResponse(Response $response, ?\DateTime $lastUpdateDate, bool $immutable): void
    {
        if (!is_string($response->getContent())) {
            return;
        }

        $etag = \hash($this->hashAlgo, $response->getContent());
        $this->makeResponseCacheable($response, $etag, $lastUpdateDate, $immutable);
    }

    public function makeResponseCacheable(Response $response, string $etag, ?\DateTime $lastUpdateDate, bool $immutable): void
    {
        $response->setCache([
            'etag' => $etag,
            'max_age' => $immutable ? 604800 : 600,
            's_maxage' => $immutable ? 2678400 : 3600,
            'public' => true,
            'private' => false,
            'immutable' => $immutable,
        ]);

        if ($lastUpdateDate !== null) {
            $response->setLastModified($lastUpdateDate);
        }
    }
}
