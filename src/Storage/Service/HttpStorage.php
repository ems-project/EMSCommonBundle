<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Service;

use EMS\CommonBundle\Common\HttpClientFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HttpStorage extends AbstractUrlStorage
{
    /** @var string */
    private $baseUrl;
    /** @var string */
    private $getUrl;
    /** @var string|null */
    private $authKey;

    public function __construct(LoggerInterface $logger, string $baseUrl, string $getUrl, int $usage, ?string $authKey = null)
    {
        parent::__construct($logger, $usage);
        $this->baseUrl = $baseUrl;
        $this->getUrl = $getUrl;
        $this->authKey = $authKey;
    }

    private function getClient(): Client
    {
        static $client = null;
        if (null === $client) {
            $client = HttpClientFactory::create($this->baseUrl, [], 30, true);
        }

        return $client;
    }

    protected function getBaseUrl(): string
    {
        return $this->baseUrl.$this->getUrl;
    }

    protected function getPath(string $hash, string $ds = '/'): string
    {
        return $this->baseUrl.$this->getUrl.$hash;
    }

    public function health(): bool
    {
        try {
            $result = $this->getClient()->get('/status.json');
<<<<<<< HEAD
            if (200 == $result->getStatusCode()) {
                $status = json_decode($result->getBody(), true);
=======
            if ($result->getStatusCode() == 200) {
                $status = \json_decode($result->getBody()->getContents(), true);
>>>>>>> ems/develop
                if (isset($status['status']) && in_array($status['status'], ['green', 'yellow'])) {
                    return true;
                }
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
<<<<<<< HEAD

        return false;
=======
>>>>>>> ems/develop
    }

    public function read(string $hash, bool $confirmed = true): StreamInterface
    {
        try {
<<<<<<< HEAD
            return $this->getClient()->get($this->getUrl.$hash)->getBody();
        } catch (\Exception $e) {
=======
            return $this->getClient()->get($this->getUrl . $hash)->getBody();
        } catch (\Throwable $e) {
>>>>>>> ems/develop
            throw new NotFoundHttpException($hash);
        }
    }

    public function initUpload(string $hash, int $size, string $name, string $type): bool
    {
        try {
            $result = $this->getClient()->post('/api/file/init-upload/'.urlencode($hash).'/'.$size.'?name='.urlencode($name).'&type='.urlencode($type), [
                'headers' => [
                    'X-Auth-Token' => $this->authKey,
                ],
            ]);

<<<<<<< HEAD
            return 200 === $result->getStatusCode();
        } catch (\Exception $e) {
=======
            return $result->getStatusCode() === 200;
        } catch (\Throwable $e) {
>>>>>>> ems/develop
            throw new NotFoundHttpException($hash);
        }
    }

    public function addChunk(string $hash, string $chunk): bool
    {
        try {
            $result = $this->getClient()->post('/api/file/upload-chunk/'.urlencode($hash), [
                'headers' => [
                    'X-Auth-Token' => $this->authKey,
                ],
                'body' => $chunk,
            ]);
<<<<<<< HEAD

            return 200 === $result->getStatusCode();
        } catch (\Exception $e) {
=======
            return $result->getStatusCode() === 200;
        } catch (\Throwable $e) {
>>>>>>> ems/develop
            throw new NotFoundHttpException($hash);
        }
    }

    public function finalizeUpload(string $hash): bool
    {
        return $this->head($hash);
    }

    public function head(string $hash): bool
    {
        try {
<<<<<<< HEAD
            return 200 === $this->getClient()->head($this->getUrl.$hash)->getStatusCode();
        } catch (Exception $e) {
=======
            return $this->getClient()->head($this->getUrl . $hash)->getStatusCode() === 200;
        } catch (\Throwable $e) {
>>>>>>> ems/develop
            throw new NotFoundHttpException($hash);
        }
    }

    public function create(string $hash, string $filename): bool
    {
        try {
            $this->getClient()->request('POST', '/api/file', [
                'multipart' => [
                    [
                        'name' => 'upload',
                        'contents' => fopen($filename, 'r'),
                    ],
                ],
                'headers' => [
                    'X-Auth-Token' => $this->authKey,
                ],
            ]);

            return true;
        } catch (GuzzleException $e) {
            return false;
        }
    }

    public function getSize(string $hash): int
    {
        try {
            $context = stream_context_create(['http' => ['method' => 'HEAD']]);
            $fd = fopen($this->baseUrl.$this->getUrl.$hash, 'rb', false, $context);

            if (false === $fd) {
                throw new NotFoundHttpException($hash);
            }

            $metas = stream_get_meta_data($fd);
            foreach ($metas['wrapper_data'] ?? [] as $meta) {
                if (preg_match('/^content\-length: (.*)$/i', $meta, $matches, PREG_OFFSET_CAPTURE)) {
                    return intval($matches[1][0]);
                }
            }
        } catch (\Throwable $e) {
        }
        throw new NotFoundHttpException($hash);
    }

    public function __toString(): string
    {
        return HttpStorage::class." ($this->baseUrl)";
    }

    public function remove(string $hash): bool
    {
        return false;
    }
}
