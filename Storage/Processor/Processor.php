<?php

namespace EMS\CommonBundle\Storage\Processor;

use EMS\CommonBundle\Helper\ArrayTool;
use EMS\CommonBundle\Storage\NotFoundException;
use EMS\CommonBundle\Storage\StorageManager;
use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

    public function __construct(StorageManager $storageManager, LoggerInterface $logger)
    {
        $this->storageManager = $storageManager;
        $this->logger = $logger;
    }

    public function getResponse(Request $request, string $hash, string $configHash, string $filename)
    {
        $configJson = json_decode($this->storageManager->getContents($configHash), true);
        $config = new Config($this->storageManager, $configHash, $hash, $configHash, $configJson);
        $cacheKey = $config->getCacheKey();

        $cacheResponse = new Response();
        $cacheResponse->setPublic()->setLastModified($config->getLastUpdateDate())->setEtag($cacheKey);
        if ($cacheResponse->isNotModified($request)) {
            return $cacheResponse;
        }

        $handler = $this->getResource($config, $filename, $request->headers->getCacheControlDirective('no-cache') === true);

        if (! $handler instanceof StreamInterface) {
            $handler = new Stream($handler);
        }

        $response = $this->getResponseFromStreamInterface($handler, $request);

        $response->headers->add([
            'Content-Disposition' => $config->getDisposition() . '; ' . HeaderUtils::toString(array('filename' => $filename), ';'),
            'Content-Type' => $config->getMimeType(),
        ]);

        $response->setPublic()->setLastModified($config->getLastUpdateDate())->setEtag($cacheKey);
        return $response;
    }

    public function createResponse(Request $request, string $processor, string $assetHash, array $options = []): Response
    {
        @trigger_error(sprintf('The "%s::createResponse" method is deprecated. Use %s::getResponse instead.', Processor::class, Processor::class), E_USER_DEPRECATED);

        $options['_config_type'] = $request->query->get('type', null);
        $config = $this->getConfig($processor, $assetHash, $options);

        $cacheKey = $config->getCacheKey();
        $lastCacheDate = $this->storageManager->getLastCacheDate($cacheKey, $processor);

        $lastModified = $config->isValid($lastCacheDate) ? $lastCacheDate : new \DateTime();

        $cacheResponse = new Response();
        $cacheResponse->setPublic()->setLastModified($lastModified)->setEtag($cacheKey);
        if ($cacheResponse->isNotModified($request)) {
            return $cacheResponse;
        }

        $asset = $this->generate($config);

        return $this->buildResponse($asset, $config->getMimeType(), $processor, $cacheKey);
    }

    /**
     * The assest was already rendered and cached (see process function).
     * We can not build the config because it can be changed at runtime.
     */
    public function fromCache(Request $request, string $processor, string $assetHash, string $configHash): Response
    {
        $context = $processor;
        $cacheKey = $assetHash . '_' . $configHash;

        $lastModified = $this->storageManager->getLastCacheDate($cacheKey, $context);

        $cacheResponse = new Response();
        $cacheResponse->setPublic()->setLastModified($lastModified)->setEtag($cacheKey);
        if ($cacheResponse->isNotModified($request)) {
            return $cacheResponse;
        }

        if (!$lastModified) {
            throw new NotFoundHttpException();
        }

        $asset = $this->storageManager->getCacheFile($cacheKey, $context);

        return $this->buildResponse($asset, $request->get('type'), $processor, $cacheKey);
    }

    /**
     * Called from twig function, the config is used for generating the url
     */
    public function process(string $processor, string $assetHash, array $options = []): Config
    {
        $config = $this->getConfig($processor, $assetHash, $options);

        $lastCacheDate = $this->storageManager->getLastCacheDate($config->getCacheKey(), $processor);

        if (!$config->isValid($lastCacheDate)) {
            $this->generate($config);
        }

        return $config;
    }

    private function buildResponse($file, $type, $processor, $cacheKey): BinaryFileResponse
    {
        $lastModified = $this->storageManager->getLastCacheDate($cacheKey, $processor);

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', $type);
        $response->headers->set('X-Ems-Cached-Files', '1');
        $response->setPublic()->setLastModified($lastModified)->setEtag($cacheKey);

        return $response;
    }

    private function getConfig(string $processor, string $hash, array $options): Config
    {
        $jsonOptions = ArrayTool::normalizeAndSerializeArray($options);
        $configHash = $this->storageManager->computeStringHash($jsonOptions);
        try {
            return new Config($this->storageManager, $processor, $hash, $configHash, $options);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
            return new Config($this->storageManager, $processor, $hash, $configHash);
        }
    }

    private function generate(Config $config): string
    {
        @trigger_error(sprintf('The "%s::generate" method is deprecated s. Use "%s::generateResource" instead.', Processor::class, Processor::class), E_USER_DEPRECATED);

        $generated = $this->generateImage($config);
        $this->storageManager->createCacheFile($config->getCacheKey(), $generated, $config->getProcessor());

        return $generated;
    }


    private function generateResource(Config $config)
    {
        $file = null;
        if (!$config->isCacheableResult()) {
            $file = $this->storageManager->getPublicImage('big-logo.png');
        } elseif ($config->getFilename()) {
            $file = $config->getFilename();
        }
        if ($config->getConfigType() === 'image') {
            return fopen($this->generateImage($config, $file), 'r');
        }

        throw new \Exception('not able to generate processor resource');
    }

    private function generateImage(Config $config, string $filename = null): string
    {
        $image = new Image($config);

        if ($watermark = $config->getWatermark()) {
            $image->setWatermark($this->storageManager->getFile($watermark));
        }

        try {
            if ($filename) {
                $file = $filename;
            } else {
                $file = $this->storageManager->getFile($config->getAssetHash());
            }
            $generatedImage = $config->isSvg() ? $file : $image->generate($file);
        } catch (\InvalidArgumentException $e) {
            $generatedImage = $image->generate($this->storageManager->getPublicImage('big-logo.png'));
        }

        return $generatedImage;
    }

    private function getResourceFromAsset(Config $config)
    {
        if ($config->getFilename()) {
            return fopen($config->getFilename(), 'r');
        }

        return $this->storageManager->getResource($config->getAssetHash());
    }

    private function getGeneratedResourceFromCache(Config $config)
    {
        if (!$config->isCacheableResult()) {
            return null;
        }

        try {
            return $this->storageManager->getResource($config->getAssetHash(), $config->getConfigHash());
        } catch (NotFoundException $e) {
        } catch (\Exception $e) {
            $this->logger->warning('log.unexpected_exception', ['error_message' => $e->getMessage()]);
        }

        return null;
    }

    private function saveGeneratedResourceToCache($generatedResource, Config $config, string $filename)
    {
        if (!$config->isCacheableResult()) {
            return;
        }

        try {
            $this->storageManager->cacheResource($generatedResource, $config->getAssetHash(), $config->getConfigHash(), $filename, $config->getMimeType(), 1);
        } catch (\Exception $e) {
            $this->logger->warning('log.unexpected_exception', ['error_message' => $e->getMessage()]);
        }
    }

    public function getResource(Config $config, string $filename, bool $noCache = false)
    {
        if ($config->getStorageContext() === null) {
            return $this->getResourceFromAsset($config);
        }

        if (!$noCache && ($cachedResource = $this->getGeneratedResourceFromCache($config)) !== null) {
            return $cachedResource;
        }

        $generatedResource = $this->generateResource($config);
        $this->saveGeneratedResourceToCache($generatedResource, $config, $filename);
        return $generatedResource;
    }

    private function getResponseFromStreamInterface(StreamInterface $streamInterface, Request $request): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($streamInterface) {
            if ($streamInterface->isSeekable()) {
                $streamInterface->rewind();
            }

            while (!$streamInterface->eof()) {
                echo $streamInterface->read(self::BUFFER_SIZE);
            }
            $streamInterface->close();
        });

        if (null === $fileSize = $streamInterface->getSize()) {
            return $response;
        }
        $response->headers->set('Content-Length', strval($fileSize));

        if ($streamInterface->isSeekable()) {
            $response->headers->set('Accept-Ranges', $request->isMethodSafe() ? 'bytes' : 'none');
        }

        $range = $request->headers->get('Range');

        if ($range === null) {
            return $response;
        }

        list($start, $end) = explode('-', substr($range, 6), 2) + [0];

        $end = ('' === $end) ? $fileSize - 1 : (int) $end;

        if ('' === $start) {
            $start = $fileSize - $end;
            $end = $fileSize - 1;
        } else {
            $start = (int) $start;
        }

        if ($start > $end) {
            return $response;
        }

        if ($start < 0 || $end > $fileSize - 1) {
            $response->setStatusCode(StreamedResponse::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
            $response->headers->set('Content-Range', sprintf('bytes */%s', $fileSize));
        } elseif (0 !== $start || $end !== $fileSize - 1) {
            $offset = $start;
            $response->setStatusCode(StreamedResponse::HTTP_PARTIAL_CONTENT);
            $response->headers->set('Content-Range', sprintf('bytes %s-%s/%s', $start, $end, $fileSize));
            $response->headers->set('Content-Length', strval($end - $start + 1));

            $response->setCallback(function () use ($streamInterface, $offset, $end) {
                $buffer = self::BUFFER_SIZE;
                $streamInterface->seek($offset);
                while (!$streamInterface->eof() && ($offset = $streamInterface->tell()) < $end) {
                    if ($offset + $buffer > $end) {
                        $buffer = $end + 1 - $offset;
                    }
                    echo $streamInterface->read($buffer);
                }
                $streamInterface->close();
            });
        }

        return $response;
    }
}
