<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\CoreApi;

use EMS\CommonBundle\Common\CoreApi\Endpoint\Data\Data;
use EMS\CommonBundle\Common\CoreApi\Endpoint\User\User;
use EMS\CommonBundle\Contracts\CoreApi\CoreApiInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\Data\DataInterface;
use EMS\CommonBundle\Contracts\CoreApi\Endpoint\User\UserInterface;
use EMS\CommonBundle\Storage\Service\HttpStorage;
use EMS\CommonBundle\Storage\StorageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;

final class CoreApi implements CoreApiInterface
{
    private Client $client;
    private StorageManager $storageManager;
    private MimeTypeGuesser $mimeTypeGuesser;

    public function __construct(Client $client, StorageManager $storageManager)
    {
        $this->client = $client;
        $this->storageManager = $storageManager;
        $this->mimeTypeGuesser = MimeTypeGuesser::getInstance();
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

    public function hashFile(string $filename): string
    {
        return $this->storageManager->computeFileHash($filename);
    }

    public function initUpload(string $hash, int $size, string $filename, string $mimetype): int
    {
        $response = $this->client->post(HttpStorage::INIT_URL, HttpStorage::initBody($hash, $size, $filename, $mimetype));

        $data = $response->getData();
        if (!$response->isSuccess() || !\is_int($data['uploaded'] ?? null)) {
            throw new \RuntimeException(\sprintf('Init upload failed due to %s', $data['error'][0] ?? 'unknown reason'));
        }

        return $data['uploaded'];
    }

    public function addChunk(string $hash, string $chunk): int
    {
        $response = $this->client->postBody(HttpStorage::addChunkUrl($hash), $chunk);

        $data = $response->getData();
        if (!$response->isSuccess() || !\is_int($data['uploaded'] ?? null)) {
            throw new \RuntimeException(\sprintf('Add chunk failed due to %s', $data['error'][0] ?? 'unknown reason'));
        }

        return $data['uploaded'];
    }

    public function uploadFile(string $realPath, string $mimeType = null): ?string
    {
        $hash = $this->hashFile($realPath);
        $filesize = \filesize($realPath);
        if (!\is_int($filesize)) {
            throw new \RuntimeException('Unexpected file size type');
        }

        $exploded = \explode(DIRECTORY_SEPARATOR, $realPath);
        $fromByte = $this->initUpload($hash, $filesize, \end($exploded), $mimeType ?? $this->mimeTypeGuesser->guess($realPath) ?? 'application/octet-stream');
        if ($fromByte < 0) {
            throw new \RuntimeException(\sprintf('Unexpected negative offset: %d', $fromByte));
        }
        if ($fromByte > $filesize) {
            throw new \RuntimeException(\sprintf('Unexpected bigger offset than the filesize: %d > %d', $fromByte, $filesize));
        }

        $handle = \fopen($realPath, 'r');
        if (false === $handle) {
            throw new \RuntimeException(\sprintf('Unexpected error while open the archive %s', $realPath));
        }
        if ($fromByte > 0) {
            if (0 !== \fseek($handle, $fromByte)) {
                throw new \RuntimeException(\sprintf('Unexpected error while seeking the file pointer at position %s', $fromByte));
            }
        }

        if ($fromByte === $filesize) {
            return $hash;
        }

        $uploaded = $fromByte;
        while (!\feof($handle)) {
            $chunk = \fread($handle, 819200);
            if (!\is_string($chunk)) {
                throw new \RuntimeException('Unexpected chunk type');
            }
            $uploaded = $this->addChunk($hash, $chunk);
        }
        \fclose($handle);
        if ($uploaded !== $filesize) {
            throw new \RuntimeException(\sprintf('Sizes mismatched %d vs. %d for assets %s', $uploaded, $filesize, $hash));
        }

        return $hash;
    }
}
