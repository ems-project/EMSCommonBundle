<?php

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Helper\EmsFields;
use EMS\CommonBundle\Storage\Processor\Config;
use EMS\CommonBundle\Storage\Processor\Processor;
use EMS\CommonBundle\Storage\StorageManager;
use function GuzzleHttp\Psr7\mimetype_from_filename;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class RequestRuntime implements RuntimeExtensionInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var StorageManager */
    private $storageManager;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var Processor */
    private $processor;

    /** @var string */
    private $cacheDir;

    public function __construct(RequestStack $requestStack, StorageManager $storageManager, UrlGeneratorInterface $urlGenerator, Processor $processor, string $cacheDir)
    {
        $this->requestStack = $requestStack;
        $this->storageManager = $storageManager;
        $this->urlGenerator = $urlGenerator;
        $this->processor = $processor;
        $this->cacheDir = $cacheDir;
    }

    public static function endsWith($haystack, $needle)
    {
        $length = \strlen($needle);
        if (0 == $length) {
            return true;
        }

        return \substr($haystack, -$length) === $needle;
    }

    /**
     * @return mixed
     */
    public function localeAttribute(array $array, string $attribute)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return '';
        }

        $locale = $request->getLocale();

        return isset($array[$attribute.$locale]) ? $array[$attribute.$locale] : '';
    }

    /**
     * @param string $filenameField
     * @param string $mimeTypeField
     * @param int    $referenceType
     */
    public function assetPath(array $fileField, array $assetConfig = [], string $route = 'ems_asset', string $fileHashField = EmsFields::CONTENT_FILE_HASH_FIELD, $filenameField = EmsFields::CONTENT_FILE_NAME_FIELD, $mimeTypeField = EmsFields::CONTENT_MIME_TYPE_FIELD, $referenceType = UrlGeneratorInterface::RELATIVE_PATH): string
    {
        $config = $assetConfig;

        $hash = null;
        if (isset($fileField[EmsFields::CONTENT_FILE_HASH_FIELD_])) {
            $hash = $fileField[EmsFields::CONTENT_FILE_HASH_FIELD_];
        } elseif (isset($fileField[$fileHashField])) {
            $hash = $fileField[$fileHashField];
        }

        $filename = 'asset.bin';
        if (isset($fileField[EmsFields::CONTENT_FILE_NAME_FIELD_])) {
            $filename = $fileField[EmsFields::CONTENT_FILE_NAME_FIELD_];
        } elseif (isset($fileField[$filenameField])) {
            $filename = $fileField[$filenameField];
        }

        $mimeType = 'application/bin';
        if (isset($fileField[EmsFields::CONTENT_MIME_TYPE_FIELD_])) {
            $mimeType = $fileField[EmsFields::CONTENT_MIME_TYPE_FIELD_];
        } elseif (isset($fileField[$mimeTypeField])) {
            $mimeType = $fileField[$mimeTypeField];
        }

        //We are generating an image
        if (isset($config[EmsFields::ASSET_CONFIG_TYPE]) && EmsFields::ASSET_CONFIG_TYPE_IMAGE === $config[EmsFields::ASSET_CONFIG_TYPE]) {
            //an SVG image wont be reworked
            if ($mimeType && \preg_match('/image\/svg.*/', $mimeType)) {
                $config[EmsFields::ASSET_CONFIG_MIME_TYPE] = $mimeType;
                if (!self::endsWith($filename, '.svg')) {
                    $filename .= '.svg';
                }
            } elseif (isset($config[EmsFields::ASSET_CONFIG_QUALITY]) && !$config[EmsFields::ASSET_CONFIG_QUALITY]) {
                $config[EmsFields::ASSET_CONFIG_MIME_TYPE] = 'image/png';
                if (!self::endsWith($filename, '.png')) {
                    $filename .= '.png';
                }
            } else {
                $config[EmsFields::ASSET_CONFIG_MIME_TYPE] = 'image/jpeg';
                if (!self::endsWith($filename, '.jpeg') && !self::endsWith($filename, '.jpg')) {
                    $filename .= '.jpg';
                }
            }
        } elseif (!$mimeType) {
            $config[EmsFields::ASSET_CONFIG_MIME_TYPE] = mimetype_from_filename($filename) ?? 'application/octet-stream';
        } else {
            $config[EmsFields::ASSET_CONFIG_MIME_TYPE] = $mimeType;
        }

        $hashConfig = $this->storageManager->saveConfig($config);

        if (isset($config[EmsFields::ASSET_CONFIG_GET_FILE_PATH]) && $config[EmsFields::ASSET_CONFIG_GET_FILE_PATH]) {
            $configObj = new Config($this->storageManager, $hash, $hashConfig, $config);

            $filesystem = new Filesystem();
            if ($hash) {
                $filesystem->mkdir($this->cacheDir.DIRECTORY_SEPARATOR.'ems_asset_path'.DIRECTORY_SEPARATOR.$hashConfig);
                $cacheFilename = $this->cacheDir.DIRECTORY_SEPARATOR.'ems_asset_path'.DIRECTORY_SEPARATOR.$hashConfig.DIRECTORY_SEPARATOR.$hash;
            } else {
                $filesystem->mkdir($this->cacheDir.DIRECTORY_SEPARATOR.'ems_asset_path');
                $cacheFilename = $this->cacheDir.DIRECTORY_SEPARATOR.'ems_asset_path'.DIRECTORY_SEPARATOR.$hashConfig;
            }

            if (!$filesystem->exists($cacheFilename)) {
                $stream = $this->processor->getStream($configObj, $filename);
                \file_put_contents($cacheFilename, $stream->getContents());
            }

            return $cacheFilename;
        }

        $parameters = [
            'hash_config' => $hashConfig,
            'filename' => \basename($filename),
            'hash' => $hash ?? $hashConfig,
        ];

        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }
}
