<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi\Endpoint\Data;

use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Data\DataInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Data\DraftInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Data\RevisionInterface;

final class Data implements DataInterface
{
    private Client $client;
    /** @var string[] */
    private array $endPoint;

    public function __construct(Client $client, string $contentType)
    {
        $this->client = $client;
        $this->endPoint = ['api', 'data', $contentType];
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public function create(array $rawData, ?string $ouuid = null): DraftInterface
    {
        $resource = $this->makeResource('create', $ouuid);

        return new Draft($this->client->post($resource, $rawData));
    }

    public function discard(int $revisionId): bool
    {
        $resource = $this->makeResource('discard', \strval($revisionId));

        return $this->client->post($resource)->isSuccess();
    }

    public function delete(string $ouuid): bool
    {
        $resource = $this->makeResource('delete', $ouuid);

        return $this->client->post($resource)->isSuccess();
    }

    public function finalize(int $revisionId): string
    {
        $resource = $this->makeResource('finalize', \strval($revisionId));

        $data = $this->client->post($resource)->getData();

        return $data['ouuid'];
    }

    public function get(string $ouuid): RevisionInterface
    {
        $resource = $this->makeResource($ouuid);

        return new Revision($this->client->get($resource));
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public function replace(string $ouuid, array $rawData): DraftInterface
    {
        $resource = $this->makeResource('replace', $ouuid);

        return new Draft($this->client->post($resource, $rawData));
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public function update(string $ouuid, array $rawData): DraftInterface
    {
        $resource = $this->makeResource('merge', $ouuid);

        return new Draft($this->client->post($resource, $rawData));
    }

    private function makeResource(?string ...$path): string
    {
        return \implode('/', \array_merge($this->endPoint, \array_filter($path)));
    }
}
