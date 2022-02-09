<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi\Endpoint\Admin;

use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\ConfigInterface;

class Config implements ConfigInterface
{
    private Client $client;
    /** @var string[] */
    private array $endPoint;
    private string $configType;

    public function __construct(Client $client, string $configType)
    {
        $this->client = $client;
        $this->configType = $configType;
        $this->endPoint = ['api', 'admin', $configType];
    }

    public function getType(): string
    {
        return $this->configType;
    }

    /**
     * @return string[]
     */
    public function index(): array
    {
        return $this->client->get(\implode('/', $this->endPoint))->getData();
    }

    /**
     * @return mixed[]
     */
    public function get(string $name): array
    {
        return $this->client->get(\implode('/', \array_merge($this->endPoint, [$name])))->getData();
    }

    public function update(string $name, array $data): void
    {
        $this->client->post(\implode('/', \array_merge($this->endPoint, [$name])), $data);
    }
}
