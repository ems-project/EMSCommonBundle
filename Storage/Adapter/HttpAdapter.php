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
    public function __construct(string $path, $baseUrl)
    {
        if($baseUrl) {
            $this->client = HttpClientFactory::create($baseUrl);
            $this->fileAdapter = new FileAdapter($path);
        }
        else {
            $this->client = false;
        }
    }

    /**
     * @inheritdoc
     */
    public function exists(string $sha1): bool
    {
        if($this->client === false)  {
            return false;
        }
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
        if($this->client === false)  {
            throw new \Exception('HttpAdapter not initialized');
        }

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
        if($this->client === false)  {
            return true;
        }

        $response = $this->client->request('HEAD');

        return $response->getStatusCode() === 200;
    }
}