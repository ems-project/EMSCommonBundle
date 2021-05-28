<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi;

use EMS\CommonBundle\Contracts\CoreApi\CoreApiFactoryInterface;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use EMS\CommonBundle\Storage\StorageManager;
use Psr\Log\LoggerInterface;

final class CoreApiFactory implements CoreApiFactoryInterface
{
    private LoggerInterface $logger;
    private StorageManager $storageManager;

    public function __construct(LoggerInterface $logger, StorageManager $storageManager)
    {
        $this->logger = $logger;
        $this->storageManager = $storageManager;
    }

    public function create(string $baseUrl): CoreApiInterface
    {
        $client = new Client($baseUrl, $this->logger);

        return new CoreApi($client, $this->storageManager);
    }
}
