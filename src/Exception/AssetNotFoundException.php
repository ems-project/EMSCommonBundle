<?php

namespace EMS\CommonBundle\Exception;

class AssetNotFoundException extends \RuntimeException
{
    public function __construct(string $hash)
    {
        parent::__construct(sprintf('Asset identified by the hash %s not found', $hash));
    }
}
