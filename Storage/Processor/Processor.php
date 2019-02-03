<?php

namespace EMS\CommonBundle\Storage\Processor;

use EMS\CommonBundle\Storage\StorageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Processor
{
    /** @var StorageManager */
    private $storageManager;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(StorageManager $storageManager, LoggerInterface $logger)
    {
        $this->storageManager = $storageManager;
        $this->logger = $logger;
    }

    public function createResponse(Request $request, string $processor, string $assetHash, array $options = []): Response
    {
        $options['_type'] = $request->query->get('type', null);
        $config = $this->getConfig($processor, $assetHash, $options);

        $cacheKey = $config->getCacheKey();
        $lastCacheDate = $this->storageManager->getLastCacheDate($cacheKey, $processor);

        $lastModified = $config->isValid($lastCacheDate) ? $lastCacheDate : new \DateTime();

        $cacheResponse = new Response();
        $cacheResponse->setPrivate()->setLastModified($lastModified)->setEtag($cacheKey);
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
        $cacheResponse->setPrivate()->setLastModified($lastModified)->setEtag($cacheKey);
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
        $response->headers->set('X-Ems-Cached-Files', 1);
        $response->setPrivate()->setLastModified($lastModified)->setEtag($cacheKey);

        return $response;
    }

    private function getConfig(string $processor, string $hash, array $options): Config
    {
        try {
            return new Config($processor, $hash, $options);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return new Config($processor, $hash);
        }
    }

    private function generate(Config $config): string
    {
        $generated = $this->generateImage($config);
        $this->storageManager->createCacheFile($config->getCacheKey(), $generated, $config->getProcessor());

        return $generated;
    }

    private function generateImage(Config $config): string
    {
        $image = new Image($config);

        if ($watermark = $config->getWatermark()) {
            $image->setWatermark($this->storageManager->getFile($watermark));
        }

        try {
            $file = $this->storageManager->getFile($config->getAssetHash());
            $generatedImage = $config->isSvg() ? $file : $image->generate($file);
        } catch (\InvalidArgumentException $e) {
            $generatedImage = $image->generate($this->storageManager->getPublicImage('big-logo.png'));
        }

        return $generatedImage;
    }
}