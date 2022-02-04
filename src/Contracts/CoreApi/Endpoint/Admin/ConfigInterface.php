<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi\Endpoint\Admin;

interface ConfigInterface
{
    public function getType(): string;

    /**
     * @return iterable<string, mixed[]>
     */
    public function index(): iterable;

    /**
     * @return mixed[]
     */
    public function get(string $name): array;
}
