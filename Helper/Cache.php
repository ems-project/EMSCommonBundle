<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Helper;

use Symfony\Component\HttpFoundation\Response;

final class Cache
{
    /** @var string */
    private $hashAlgo;

    public function __construct(string $hashAlgo)
    {
        $this->hashAlgo = $hashAlgo;
    }

    public function generateEtag(Response $response, ?\DateTime $lastUpdateDate, bool $immutableRoute): ?string
    {
        if (!\is_string($response->getContent())) {
            return null;
        }

        return \hash($this->hashAlgo, $response->getContent());
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

        if (null !== $lastUpdateDate) {
            $response->setLastModified($lastUpdateDate);
        }
    }
}
