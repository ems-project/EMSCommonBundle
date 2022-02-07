<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin;

interface AdminInterface
{
    public function getConfig(string $typeName): ConfigInterface;
}
