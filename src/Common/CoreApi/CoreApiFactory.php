<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi;

use EMS\CommonBundle\Contracts\CoreApi\CoreApiFactoryInterface;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use Psr\Log\LoggerInterface;

final class CoreApiFactory implements CoreApiFactoryInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create(string $baseUrl): CoreApiInterface
    {
        $client = new Client($baseUrl, $this->logger);

        return new CoreApi($client);
    }
}
