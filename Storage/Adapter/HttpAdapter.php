<?php

namespace EMS\CommonBundle\Storage\Adapter;

use EMS\CommonBundle\Http\ClientFactory;

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
     * @param string $token
     */
    public function __construct(string $path, string $baseUrl, string $token)
    {
        $this->client = ClientFactory::create($baseUrl, ['X-Auth-Token' => $token]);
        $this->fileAdapter = new FileAdapter($path);
    }

    /**
     * @inheritdoc
     */
    public function exists(string $sha1): bool
    {
        $response = $this->client->request('HEAD', '/api/file/'.$sha1);

        return $response->getStatusCode() === 200;
    }

    /**
     * @inheritdoc
     */
    public function read(string $sha1): string
    {
        if ($this->fileAdapter->exists($sha1)) {
            return $this->fileAdapter->read($sha1);
        }

        $response = $this->client->request('GET', '/api/file/'.$sha1);

        return $this->fileAdapter->create($sha1, $response->getBody()->getContents());
    }

    /**
     * @todo make create call
     *
     * @param string $sha1
     * @param string $content
     *
     * @return string
     */
    public function create(string $sha1, string $content): string
    {
        throw new \Exception('not supported!');
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