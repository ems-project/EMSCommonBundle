<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi;

use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Data\DataInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\User\UserInterface;
use EMS\CommonBundle\Contracts\CoreApi\Exception\BaseUrlNotDefinedExceptionInterface;
use EMS\CommonBundle\Contracts\CoreApi\Exception\NotAuthenticatedExceptionInterface;
use Psr\Log\LoggerInterface;

interface CoreApiInterface
{
    public const HEADER_TOKEN = 'X-Auth-Token';

    /**
     * @throws CoreApiExceptionInterface
     * @throws NotAuthenticatedExceptionInterface
     */
    public function authenticate(string $username, string $password): CoreApiInterface;

    public function data(string $contentType): DataInterface;

    /**
     * @throws BaseUrlNotDefinedExceptionInterface
     */
    public function getBaseUrl(): string;

    public function getToken(): string;

    public function isAuthenticated(): bool;

    public function setLogger(LoggerInterface $logger): void;

    public function setToken(string $token): void;

    /**
     * @throws BaseUrlNotDefinedExceptionInterface
     * @throws NotAuthenticatedExceptionInterface
     */
    public function test(): bool;

    public function user(): UserInterface;

    public function hashFile(string $filename): string;

    public function initUpload(string $hash, int $size, string $filename, string $mimetype): int;

    public function addChunk(string $hash, string $chunk): int;
}
