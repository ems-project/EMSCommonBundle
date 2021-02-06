<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi;

use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Data\DataInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\User\UserInterface;
use Psr\Log\LoggerInterface;

interface CoreApiInterface
{
    public const HEADER_TOKEN = 'X-Auth-Token';

    public function authenticate(string $username, string $password): CoreApiInterface;

    public function data(string $contentType): DataInterface;

    public function getBaseUrl(): string;

    public function getToken(): string;

    public function isAuthenticated(): bool;

    public function setLogger(LoggerInterface $logger): void;

    public function test(): bool;

    public function user(): UserInterface;
}
