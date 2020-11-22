<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Exception;

use Elastica\Exception\NotFoundException as ElasticaNotFoundException;

class NotFoundException extends ElasticaNotFoundException
{
    public function __construct(?string $ouuid = null, ?string $index = null)
    {
        if ($ouuid !== null && $index !== null) {
            parent::__construct(sprintf('Document %s not found in index/alias %s', $ouuid, $index));
        } elseif ($ouuid !== null) {
            parent::__construct(sprintf('Document %s not found', $ouuid));
        } else {
            parent::__construct(sprintf('Not found exception'));
        }
    }
}
