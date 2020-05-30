<?php

namespace EMS\CommonBundle\Storage\Processor;

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

class Processor
{
    /** @var StorageManager */
    private $storageManager;
    /** @var LoggerInterface */
    private $logger;

    const BUFFER_SIZE = 8192;
    /**  @var Cache */
    private $cacheHelper;

    public function __construct(StorageManager $storageManager, LoggerInterface $logger, Cache $cacheHelper)
    {
        $this->storageManager = $storageManager;
        $this->logger = $logger;
        $this->cacheHelper = $cacheHelper;
    }


    public function getResponse(Request $request, string $hash, string $configHash, string $filename, bool $immutableRoute = false): Response
    {
        $configJson = json_decode($this->storageManager->getContents($configHash), true);
        $config = new Config($this->storageManager, $configHash, $hash, $configHash, $configJson);
        $cacheKey = $config->getCacheKey();

        $cacheResponse = new Response();
        $this->cacheHelper->makeResponseCacheable($cacheResponse, $cacheKey, $config->getLastUpdateDate(), $immutableRoute);
        if ($cacheResponse->isNotModified($request)) {
            return $cacheResponse;
        }

        $stream = $this->getStream($config, $filename);

        $response = $this->getResponseFromStreamInterface($stream, $request);

        $response->headers->add([
            'Content-Disposition' => $config->getDisposition() . '; ' . HeaderUtils::toString(array('filename' => $filename), ';'),
            'Content-Type' => $config->getMimeType(),
        ]);

        $this->cacheHelper->makeResponseCacheable($response, $cacheKey, $config->getLastUpdateDate(), $immutableRoute);
        return $response;
    }

    /**
     * @return resource
     */
    private function generateResource(Config $config)
    {
        $file = null;
        if (!$config->isCacheableResult()) {
            $file = $this->storageManager->getPublicImage('big-logo.png');
        } elseif ($config->getFilename()) {
            $file = $config->getFilename();
        }
        if ($config->getConfigType() === 'image') {
            $resource = \fopen($this->generateImage($config, $file), 'r');
            if ($resource === false) {
                throw new \Exception('It was not able to open the generated image');
            }
            return $resource;
        }

        throw new \Exception(sprintf('not able to generate file for the config %s', $config->getConfigHash()));
    }

    private function hashToFilename(string $hash): string
    {
        $filename = (string) tempnam(sys_get_temp_dir(), 'EMS');
        file_put_contents($filename, $this->storageManager->getContents($hash));
        return $filename;
    }


    private function generateImage(Config $config, string $filename = null): string
    {
        $image = new Image($config);

        $watermark = $config->getWatermark();
        if ($watermark !== null && $this->storageManager->head($watermark)) {
            $image->setWatermark($this->hashToFilename($watermark));
        }

        try {
            if ($filename) {
                $file = $filename;
            } else {
                $file = $this->hashToFilename($config->getAssetHash());
            }
            $generatedImage = $config->isSvg() ? $file : $image->generate($file);
        } catch (\InvalidArgumentException $e) {
            $generatedImage = $image->generate($this->storageManager->getPublicImage('big-logo.png'));
        }

        return $generatedImage;
    }

    private function getStreamFomFilename(string $filename): StreamInterface
    {
        $resource = \fopen($filename, 'r');
        if ($resource === false) {
            throw new NotFoundException($filename);
        }
        return new Stream($resource);
    }

    private function getStreamFromAsset(Config $config): StreamInterface
    {
        if ($config->getFilename() !== null) {
            return $this->getStreamFomFilename($config->getFilename());
        }

        return $this->storageManager->getStream($config->getAssetHash());
    }

//    private function getGeneratedResourceFromCache(Config $config)
//    {
//        if (!$config->isCacheableResult()) {
//            return null;
//        }
//
//        try {
//            return $this->storageManager->getResource($config->getAssetHash(), $config->getConfigHash());
//        } catch (NotFoundException $e) {
//        } catch (\Exception $e) {
//            $this->logger->warning('log.unexpected_exception', ['error_message' => $e->getMessage()]);
//        }
//
//        return null;
//    }
//
//    private function saveGeneratedResourceToCache($generatedResource, Config $config, string $filename)
//    {
//        if (!$config->isCacheableResult()) {
//            return;
//        }
//
//        try {
//            $this->storageManager->cacheResource($generatedResource, $config->getAssetHash(), $config->getConfigHash(), $filename, $config->getMimeType(), 1);
//        } catch (\Exception $e) {
//            $this->logger->warning('log.unexpected_exception', ['error_message' => $e->getMessage()]);
//        }
//    }
//
    public function getStream(Config $config, string $filename, bool $noCache = false): StreamInterface
    {
        if ($config->getCacheContext() === null) {
            return $this->getStreamFromAsset($config);
        }

        //TODO: try to get
//        if (!$noCache && ($cachedResource = $this->getGeneratedResourceFromCache($config)) !== null) {
//            return $cachedResource;
//        }
        $this->logger->warning('log.unexpected_exception', ['error_message' => 'Generate cache asset']);

        $generatedResource = $this->generateResource($config);
        //TODO: try to save file
//        $this->saveGeneratedResourceToCache($generatedResource, $config, $filename);
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
        } else if ($streamRange->isPartial()) {
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
