<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi;

use EMS\CommonBundle\Common\CoreApi\Exception\BaseUrlNotDefinedException;
use EMS\CommonBundle\Common\CoreApi\Exception\NotAuthenticatedException;
use EMS\CommonBundle\Common\CoreApi\Exception\NotSuccessfulException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Client
{
    /** @var array<string, string> */
    private array $headers = [];
    private string $baseUrl;
    private HttpClientInterface $client;
    private LoggerInterface $logger;

    public function __construct(string $baseUrl, LoggerInterface $logger)
    {
        $this->baseUrl = $baseUrl;
        $this->client = new CurlHttpClient([
            'base_uri' => $baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        $this->setLogger($logger);
    }

    public function addHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getBaseUrl(): string
    {
        if ('' === $this->baseUrl) {
            throw new BaseUrlNotDefinedException();
        }

        return $this->baseUrl;
    }

    public function getHeader(string $name): string
    {
        return $this->headers[$name];
    }

    public function get(string $resource): Result
    {
        return $this->request(Request::METHOD_GET, $resource);
    }

    /**
     * @param array<string, mixed> $body
     */
    public function post(string $resource, array $body = []): Result
    {
        return $this->request(Request::METHOD_POST, $resource, $body);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

        if ($this->client instanceof LoggerAwareInterface) {
            $this->client->setLogger($logger);
        }
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $method, string $resource, array $body = []): Result
    {
        if ('' === $this->baseUrl) {
            throw new BaseUrlNotDefinedException();
        }

        $response = $this->client->request($method, $resource, [
            'headers' => $this->headers,
            'json' => $body,
        ]);

        if (Response::HTTP_UNAUTHORIZED === $response->getStatusCode()) {
            throw new NotAuthenticatedException($response);
        }

        $result = new Result($response, $this->logger);

        if (!$result->isSuccess()) {
            throw new NotSuccessfulException($response);
        }

        return $result;
    }
}
