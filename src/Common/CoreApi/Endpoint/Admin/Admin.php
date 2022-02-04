<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi\Endpoint\Admin;

use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\AdminInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\ConfigInterface;

final class Admin implements AdminInterface
{
    /** @var ConfigInterface[] */
    private array $config;

    public function __construct(Client $client)
    {
        $this->config = [
            new ContentType($client),
        ];
    }

    /**
     * @return ConfigInterface[]
     */
    public function getConfigs(): array
    {
        return $this->config;
    }
}
