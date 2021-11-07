<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi\Search;

use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Search\SearchInterface;
use EMS\CommonBundle\Search\Search as SearchObject;

class Search implements SearchInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return array<mixed>
     */
    public function search(SearchObject $search): array
    {
        return $this->client->post('/api/search/search', ['search' => $search->serialize()])->getData();
    }
}
