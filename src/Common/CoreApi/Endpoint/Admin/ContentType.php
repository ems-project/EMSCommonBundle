<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi\Endpoint\Admin;

use EMS\CommonBundle\Common\CoreApi\Client;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin\ConfigInterface;

class ContentType implements ConfigInterface
{
    private const CONTENT_TYPE = 'ContentType';
    private Client $client;
    /** @var string[] */
    private array $endPoint;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->endPoint = ['api', 'admin', 'content-type'];
    }

    public function getType(): string
    {
        return self::CONTENT_TYPE;
    }

    /**
     * @return iterable<string, mixed[]>
     */
    public function index(): iterable
    {
        $names = $this->client->get(\implode('/', $this->endPoint))->getData();

        foreach ($names as $name) {
            yield $name => $this->get($name);
        }
    }

    /**
     * @return mixed[]
     */
    public function get(string $name): array
    {
        return $this->client->get(\implode('/', \array_merge($this->endPoint, [$name])))->getData();
    }
}
