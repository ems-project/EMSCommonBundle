<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi;

use EMS\CommonBundle\Common\CoreApi\Endpoint\Data\Data;
use EMS\CommonBundle\Common\CoreApi\Endpoint\User\User;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Data\DataInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\User\UserInterface;
use Psr\Log\LoggerInterface;

final class CoreApi implements CoreApiInterface
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function authenticate(string $username, string $password): CoreApiInterface
    {
        $response = $this->client->post('/auth-token', [
            'username' => $username,
            'password' => $password,
        ]);

        $authToken = $response->getData()['authToken'] ?? null;

        if (null !== $authToken) {
            $this->setToken($authToken);
        }

        return $this;
    }

    public function data(string $contentType): DataInterface
    {
        return new Data($this->client, $contentType);
    }

    public function getBaseUrl(): string
    {
        return $this->client->getBaseUrl();
    }

    public function getToken(): string
    {
        return $this->client->getHeader(self::HEADER_TOKEN);
    }

    public function isAuthenticated(): bool
    {
        return $this->client->hasHeader(self::HEADER_TOKEN);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->client->setLogger($logger);
    }

    public function setToken(string $token): void
    {
        $this->client->addHeader(self::HEADER_TOKEN, $token);
    }


    public function test(): bool
    {
        try {
            return $this->client->get('/api/test')->isSuccess();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function user(): UserInterface
    {
        return new User($this->client);
    }
}
