<?php

namespace EMS\CommonBundle\Storage\Adapter;

use EMS\CommonBundle\Common\HttpClientFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HttpAdapter implements AdapterInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var FileAdapter
     */
    private $fileAdapter;

    /**
     * @param string $path
     * @param string $baseUrl
     */
    public function __construct(string $path, $baseUrl)
    {
        if($baseUrl) {
            $this->client = HttpClientFactory::create($baseUrl);
            $this->fileAdapter = new FileAdapter($path);
        } else {
            $this->client = false;
        }
    }

    /**
     * @inheritdoc
     */
    public function exists(string $hash, ?string $context = null): bool
    {
        if($this->client === false)  {
            return false;
        }
        try {
            $response = $this->client->request('HEAD', '/public/file/' . $hash);
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function read(string $hash, ?string $context = null): string
    {
        if($this->client === false)  {
            throw new \Exception('HttpAdapter not initialized');
        }

        if ($this->fileAdapter->exists($hash, $context)) {
            return $this->fileAdapter->read($hash, $context);
        }

        $response = $this->client->request('GET', '/public/file/'.$hash);

        return $this->fileAdapter->create($hash, $response->getBody()->getContents(), $context);
    }

    /**
     * @inheritdoc
     */
    public function create(string $hash, string $content, ?string $context = null)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function health(): bool
    {
        if($this->client === false)  {
            return true;
        }

        $response = $this->client->request('HEAD');

        return $response->getStatusCode() === 200;
    }
}