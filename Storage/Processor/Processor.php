<?php

namespace EMS\CommonBundle\Storage\Processor;

use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Processor
{
    /** @var StorageManager */
    private $storageManager;

    public function __construct(StorageManager $storageManager)
    {
        $this->storageManager = $storageManager;
    }

    public function createResponse(Request $request, Config $config, string $hash): Response
    {
        if ($config->getConfigType() !== 'image') {
            throw new NotFoundHttpException('Processor type not found');
        }

        $type = $request->query->get('type', 'unknown');

        $context = $config->getIdentifier();
        $etag = $context . '_' . $hash;

        $configDate = $config->getLastUpdateDate();

        $cacheResponse = new Response();
        $cacheResponse->setPrivate()->setLastModified($configDate)->setEtag($etag);
        if ($cacheResponse->isNotModified($request)) {
            return $cacheResponse;
        }

        $cacheDate = $this->storageManager->getLastCacheDate($hash, $context);

        if (!$cacheDate || $cacheDate < $configDate) {
            $generated = $this->generateImage($config, $type, $hash, $context);
            $this->storageManager->createCacheFile($hash, file_get_contents($generated), $context);
        } else {
            $generated = $this->storageManager->getCacheFile($hash, $context);
        }

        $response = new BinaryFileResponse($generated);
        $response->headers->set('Content-Type', $this->getMimeType($config, $type));

        $response->headers->set('X-EMS-CACHED-FILES', 1);
        $response->setPrivate()->setLastModified($configDate)->setEtag($etag);

        return $response;
    }

    private function getMimeType(Config $config, string $type): string
    {
        if( preg_match('/image\/svg.*/', $type)){
            return $type;
        }

        return $config->getQuality() ? 'image/jpeg' : 'image/png';
    }

    private function generateImage(Config $config, string $type, string $hash): string
    {
        $image = new Image($config);

        if ($watermark = $config->getWatermark()) {
            $image->setWatermark($this->storageManager->getFile($watermark));
        }

        try {
            $file = $this->storageManager->getFile($hash);
            $generatedImage = preg_match('/image\/svg.*/', $type) ? $file :  $image->generate($file);
        } catch (\InvalidArgumentException $e) {
            $generatedImage = $image->generate($this->storageManager->getPublicImage('big-logo.png'));
        }

        return $generatedImage;
    }
}