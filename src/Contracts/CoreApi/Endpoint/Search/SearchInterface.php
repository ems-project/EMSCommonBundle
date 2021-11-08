<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi\Endpoint\Search;

use EMS\CommonBundle\Search\Search;

interface SearchInterface
{
    /**
     * @return array<mixed>
     */
    public function search(Search $search): array;
}
