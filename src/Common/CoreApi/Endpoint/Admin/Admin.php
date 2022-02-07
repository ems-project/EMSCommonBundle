<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi\Endpoint\Admin;

use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\AdminInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\ConfigInterface;

final class Admin implements AdminInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getConfig(string $typeName): ConfigInterface
    {
        return new Config($this->client, $typeName);
    }
}
