<?php

namespace EMS\CommonBundle\Storage\Adapter;

use EMS\CommonBundle\Common\HttpClientFactory;
use GuzzleHttp\Exception\GuzzleException;

class HttpAdapter implements AdapterInterface
{
    /**
     * @var string
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
    public function __construct(string $path, string $baseUrl)
    {
        $this->client = HttpClientFactory::create($baseUrl);
        $this->fileAdapter = new FileAdapter($path);
    }

    /**
     * @inheritdoc
     */
    public function exists(string $sha1): bool
    {
        try {
            $response = $this->client->request('HEAD', '/public/file/' . $sha1);
            return $response->getStatusCode() === 200;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function read(string $sha1): string
    {
        if ($this->fileAdapter->exists($sha1)) {
            return $this->fileAdapter->read($sha1);
        }

        $response = $this->client->request('GET', '/public/file/'.$sha1);

        return $this->fileAdapter->create($sha1, $response->getBody()->getContents());
    }

    /**
     * @inheritdoc
     */
    public function health(): bool
    {
        $response = $this->client->request('HEAD');

        return $response->getStatusCode() === 200;
    }
}