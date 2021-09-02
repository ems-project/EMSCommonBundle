<?php

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Helper\EmsFields;
use EMS\CommonBundle\Storage\NotSavedException;
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
    private RequestStack $requestStack;
    private StorageManager $storageManager;
    private UrlGeneratorInterface$urlGenerator;
    private Processor $processor;
    private string $cacheDir;

    public function __construct(RequestStack $requestStack, StorageManager $storageManager, UrlGeneratorInterface $urlGenerator, Processor $processor, string $cacheDir)
    {
        $this->requestStack = $requestStack;
        $this->storageManager = $storageManager;
        $this->urlGenerator = $urlGenerator;
        $this->processor = $processor;
        $this->cacheDir = $cacheDir;
    }

    public static function endsWith(string $haystack, string $needle)
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

        $hash = 'processor';
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

        // We are generating a URL for a zip file
        if (isset($config[EmsFields::ASSET_CONFIG_TYPE]) && EmsFields::ASSET_CONFIG_TYPE_ZIP === $config[EmsFields::ASSET_CONFIG_TYPE]) {
            $config[EmsFields::ASSET_CONFIG_MIME_TYPE] = 'application/zip';
            if (isset($config[EmsFields::CONTENT_FILES]) && !empty($config[EmsFields::CONTENT_FILES])) {
                if (!self::endsWith($filename, '.zip')) {
                    $filename .= '.zip';
                }
            }
        }
        try {
            $hashConfig = $this->storageManager->saveConfig($config);
        } catch (NotSavedException $e) {
            $hashConfig = $e->getHash();
        }

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

    public function assetAverageColor(string $hash): string
    {
        try {
            $config = $this->processor->configFactory($hash, [
                EmsFields::ASSET_CONFIG_TYPE => EmsFields::ASSET_CONFIG_TYPE_IMAGE,
                EmsFields::ASSET_CONFIG_RESIZE => 'free',
                EmsFields::ASSET_CONFIG_WIDTH => 1,
                EmsFields::ASSET_CONFIG_HEIGHT => 1,
                EmsFields::ASSET_CONFIG_QUALITY => 80,
                EmsFields::ASSET_CONFIG_MIME_TYPE => 'image/jpeg',
            ]);
            $stream = $this->processor->getStream($config, 'one-pixel.jpg');

            $image = \imagecreatefromstring($stream->getContents());
            if (false === $image) {
                throw new \RuntimeException('Unexpected imagecreatefromstring error');
            }
            $index = \imagecolorat($image, 0, 0);
            if (false === $index) {
                throw new \RuntimeException('Unexpected imagecolorat error');
            }
            $rgb = \imagecolorsforindex($image, $index);
            if (false === $rgb) {
                throw new \RuntimeException('Unexpected imagecolorsforindex error');
            }
            $red = \round(\round((($rgb['red'] ?? 255) / 0x33)) * 0x33);
            $green = \round(\round((($rgb['green'] ?? 255) / 0x33)) * 0x33);
            $blue = \round(\round((($rgb['blue'] ?? 255) / 0x33)) * 0x33);

            return \sprintf('#%02X%02X%02X', $red, $green, $blue);
        } catch (\Throwable $e) {
            return '#FFFFFF';
        }
    }
}
