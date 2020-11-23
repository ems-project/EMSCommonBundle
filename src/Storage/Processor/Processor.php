<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Processor;

use EMS\CommonBundle\Helper\ArrayTool;
use EMS\CommonBundle\Helper\Cache;
use EMS\CommonBundle\Storage\NotFoundException;
use EMS\CommonBundle\Storage\StorageManager;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Processor
{
    /** @var StorageManager */
    private $storageManager;
    /** @var LoggerInterface */
    private $logger;

    const BUFFER_SIZE = 8192;
    /** @var Cache */
    private $cacheHelper;

    /** @var string */
    private $projectDir;

    public function __construct(StorageManager $storageManager, LoggerInterface $logger, Cache $cacheHelper, string $projectDir)
    {
        $this->storageManager = $storageManager;
        $this->logger = $logger;
        $this->cacheHelper = $cacheHelper;
        $this->projectDir = $projectDir;
    }

    public function getResponse(Request $request, string $hash, string $configHash, string $filename, bool $immutableRoute = false): Response
    {
        $configJson = json_decode($this->storageManager->getContents($configHash), true);
        $config = new Config($this->storageManager, $hash, $configHash, $configJson);

        return $this->getStreamedResponse($request, $config, $filename, $immutableRoute);
    }

    public function getStreamedResponse(Request $request, Config $config, string $filename, bool $immutableRoute): Response
    {
        $cacheKey = $config->getCacheKey();

        $cacheResponse = new Response();
        $this->cacheHelper->makeResponseCacheable($cacheResponse, $cacheKey, $config->getLastUpdateDate(), $immutableRoute);
        if ($cacheResponse->isNotModified($request)) {
            return $cacheResponse;
        }

        $stream = $this->getStream($config, $filename);

        $response = $this->getResponseFromStreamInterface($stream, $request);

        $response->headers->add([
            'Content-Disposition' => $config->getDisposition().'; '.HeaderUtils::toString(['filename' => $filename], ';'),
            'Content-Type' => $config->getMimeType(),
        ]);

        $this->cacheHelper->makeResponseCacheable($response, $cacheKey, $config->getLastUpdateDate(), $immutableRoute);

        return $response;
    }

    /**
     * @param array<string, mixed> $configArray
     */
    public function configFactory(string $hash, array $configArray): Config
    {
        $normalizedArray = ArrayTool::normalizeAndSerializeArray($configArray);
        if (false === $normalizedArray) {
            throw new \RuntimeException('Could not normalize asset\'s processor config in JSON format.');
        }
        $configHash = $this->storageManager->computeStringHash($normalizedArray);

        return new Config($this->storageManager, $hash, $configHash, $configArray);
    }

    /**
     * @return resource
     */
    private function generateResource(Config $config, string $cacheFilename)
    {
        $file = null;
        if (!$config->isCacheableResult()) {
            $file = $this->storageManager->getPublicImage('big-logo.png');
        } elseif ($config->getFilename()) {
            $file = $config->getFilename();
        }
        if ('image' === $config->getConfigType()) {
            $resource = \fopen($this->generateImage($config, $file, $cacheFilename), 'r');
            if (false === $resource) {
                throw new \Exception('It was not able to open the generated image');
            }

            return $resource;
        }

        throw new \Exception(sprintf('not able to generate file for the config %s', $config->getConfigHash()));
    }

    private function hashToFilename(string $hash): string
    {
        $filename = (string) tempnam(sys_get_temp_dir(), 'EMS');
        \file_put_contents($filename, $this->storageManager->getContents($hash));

        return $filename;
    }

    private function generateImage(Config $config, string $filename = null, string $cacheFilename = null): string
    {
        $image = new Image($config);

        $watermark = $config->getWatermark();
        if (null !== $watermark && $this->storageManager->head($watermark)) {
            $image->setWatermark($this->hashToFilename($watermark));
        }

        try {
            if ($filename) {
                $file = $filename;
            } else {
                $file = $this->hashToFilename($config->getAssetHash());
            }
            $generatedImage = $config->isSvg() ? $file : $image->generate($file, $cacheFilename);
        } catch (\InvalidArgumentException $e) {
            $generatedImage = $image->generate($this->storageManager->getPublicImage('big-logo.png'));
        }

        return $generatedImage;
    }

    private function getStreamFomFilename(string $filename): StreamInterface
    {
        $resource = \fopen($filename, 'r');
        if (false === $resource) {
            throw new NotFoundException($filename);
        }

        return new Stream($resource);
    }

    private function getStreamFromAsset(Config $config): StreamInterface
    {
        if (null !== $config->getFilename()) {
            return $this->getStreamFomFilename($config->getFilename());
        }

        try {
            return $this->storageManager->getStream($config->getAssetHash());
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException(sprintf('File %s not found', $config->getAssetHash()));
        }
    }

    private function getCacheFilename(Config $config, string $filename): string
    {
        return join(DIRECTORY_SEPARATOR, [
            $this->projectDir,
            'public',
            'bundles',
            'emscache',
            $config->getCacheKey(),
        ]);
    }

    public function getStream(Config $config, string $filename, bool $noCache = false): StreamInterface
    {
        if (null === $config->getCacheContext()) {
            return $this->getStreamFromAsset($config);
        }

        $cacheFilename = $this->getCacheFilename($config, $filename);
        if (!$noCache && \file_exists($cacheFilename)) {
            $fp = \fopen($cacheFilename, 'r');
            if (false !== $fp) {
                return new Stream($fp);
            }
        }

        $generatedResource = $this->generateResource($config, $cacheFilename);

        return new Stream($generatedResource);
    }

    private function getResponseFromStreamInterface(StreamInterface $stream, Request $request): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($stream) {
            if ($stream->isSeekable() && $stream->tell() > 0) {
                $stream->rewind();
            }

            while (!$stream->eof()) {
                echo $stream->read(self::BUFFER_SIZE);
            }
            $stream->close();
        });

        if (null === $fileSize = $stream->getSize()) {
            return $response;
        }
        $response->headers->set('Content-Length', strval($fileSize));

        if ($stream->isSeekable()) {
            $response->headers->set('Accept-Ranges', $request->isMethodSafe() ? 'bytes' : 'none');
        }

        try {
            $streamRange = new StreamRange($request->headers, $fileSize);
        } catch (\RuntimeException $e) {
            return $response;
        }

        if (!$streamRange->isSatisfiable()) {
            $response->setStatusCode(StreamedResponse::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
            $response->headers->set('Content-Range', $streamRange->getContentRangeHeader());
        } elseif ($streamRange->isPartial()) {
            $response->setStatusCode(StreamedResponse::HTTP_PARTIAL_CONTENT);
            $response->headers->set('Content-Range', $streamRange->getContentRangeHeader());
            $response->headers->set('Content-Length', $streamRange->getContentLengthHeader());

            $response->setCallback(function () use ($stream, $streamRange) {
                $offset = $streamRange->getStart();
                $buffer = self::BUFFER_SIZE;
                $stream->seek($offset);
                while (!$stream->eof() && ($offset = $stream->tell()) < $streamRange->getEnd()) {
                    if ($offset + $buffer > $streamRange->getEnd()) {
                        $buffer = $streamRange->getEnd() + 1 - $offset;
                    }
                    echo $stream->read($buffer);
                }
                $stream->close();
            });
        }

        return $response;
    }
}
