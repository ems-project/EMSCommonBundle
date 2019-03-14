<?php

namespace EMS\CommonBundle\Storage\Processor;

use EMS\CommonBundle\Helper\ArrayTool;
use EMS\CommonBundle\Storage\NotFoundException;
use EMS\CommonBundle\Storage\StorageManager;
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

    public function __construct(StorageManager $storageManager, LoggerInterface $logger)
    {
        $this->storageManager = $storageManager;
        $this->logger = $logger;
    }

    public function getResponse(Request $request, string $hash, string $configHash, string $filename)
    {
        $configJson = $this->storageManager->getContents($configHash);
        $config = new Config($configHash, $hash, $configHash, json_decode($configJson, true));
        $cacheKey = $config->getCacheKey();

        $cacheResponse = new Response();
        $cacheResponse->setPrivate()->setLastModified($config->getLastUpdateDate())->setEtag($cacheKey);
        if ($cacheResponse->isNotModified($request)) {
            return $cacheResponse;
        }

        $handler = $this->getResource($config);

        $response =  new StreamedResponse(
            function () use ($handler) {
                while (!feof($handler)) {
                    print fread($handler, 8192);
                }
            }, 200, [
            'Content-Disposition' => $config->getDisposition().'; '.HeaderUtils::toString(array('filename' => $filename), ';'),
            'Content-Type' => $config->getMimeType(),
        ]);
        $response->setPrivate()->setLastModified($config->getLastUpdateDate())->setEtag($cacheKey);
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
        $jsonOptions = ArrayTool::normalizeAndSerializeArray($options);
        $configHash = $this->storageManager->computeStringHash($jsonOptions);
        try {
            return new Config($processor, $hash, $configHash, $options);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return new Config($processor, $hash, $configHash);
        }
    }

    private function generate(Config $config): string
    {
        @trigger_error(sprintf('The "%s::generate" method is deprecated s. Use "%s::generateResource" instead.', Processor::class, Processor::class), E_USER_DEPRECATED);

        $generated = $this->generateImage($config);
        $this->storageManager->createCacheFile($config->getCacheKey(), $generated, $config->getProcessor());

        return $generated;
    }


    /**
     * @param Config $config
     * @return resource
     */
    private function generateResource(Config $config)
    {
        $file = null;
        if(!$config->cacheableResult()) {
            $file = $this->storageManager->getPublicImage('big-logo.png');
        }
        if($config->getConfigType() === 'image') {
            return fopen($this->generateImage($config, $file), 'r');
        }

        throw new Exception('not able to generate processor resource');
    }

    private function generateImage(Config $config, string $filename = null): string
    {
        $image = new Image($config);

        if ($watermark = $config->getWatermark()) {
            $image->setWatermark($this->storageManager->getFile($watermark));
        }

        try {
            if($filename){
                $file = $filename;
            }
            else {
                $file = $this->storageManager->getFile($config->getAssetHash());
            }
            $generatedImage = $config->isSvg() ? $file : $image->generate($file);
        } catch (\InvalidArgumentException $e) {
            $generatedImage = $image->generate($this->storageManager->getPublicImage('big-logo.png'));
        }

        return $generatedImage;
    }


    /**
     * @param Config $config
     * @return resource
     */
    private function getResource(Config $config)
    {
        $cacheableResult = $config->cacheableResult();
        if(!$config->getStorageContext() || $cacheableResult) {
            try{
                return $this->storageManager->getResource($config->getAssetHash(), $config->getStorageContext());
            }
            catch (NotFoundException $e) {
                if (!$config->getStorageContext()) {
                    throw $e;
                }
            }
        }



        $generatedResource = $this->generateResource($config);
        if($cacheableResult) {
            $this->storageManager->cacheResource($generatedResource, $config->getAssetHash(), $config->getConfigHash(), 'file'.$config->getFilenameExtension(), $config->getMimeType());
        }

        return $generatedResource;
    }
}
