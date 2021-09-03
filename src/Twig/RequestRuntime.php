<?php

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Helper\EmsFields;
use EMS\CommonBundle\Storage\NotSavedException;
use EMS\CommonBundle\Storage\Processor\Config;
use EMS\CommonBundle\Storage\Processor\Processor;
use EMS\CommonBundle\Storage\StorageManager;
use GuzzleHttp\Psr7\MimeType;
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

        $hash = $fileField[EmsFields::CONTENT_FILE_HASH_FIELD_] ?? $fileField[$fileHashField] ?? 'processor';
        $filename = $fileField[EmsFields::CONTENT_FILE_NAME_FIELD_] ?? $fileField[$filenameField] ?? 'asset.bin';
        $mimeType = $fileField[EmsFields::CONTENT_MIME_TYPE_FIELD_] ?? $fileField[$mimeTypeField] ?? MimeType::fromFilename($filename) ?? 'application/octet-stream';

        $mimeType = $this->processor->overwriteMimeType($mimeType, $config);
        $filename = $this->fixFileExtension($filename, $mimeType);
        $config[EmsFields::ASSET_CONFIG_MIME_TYPE] = $mimeType;

        try {
            $hashConfig = $this->storageManager->saveConfig($config);
        } catch (NotSavedException $e) {
            $hashConfig = $e->getHash();
        }

        if (!($config[EmsFields::ASSET_CONFIG_GET_FILE_PATH] ?? false)) {
            return $this->urlGenerator->generate($route, [
                'hash_config' => $hashConfig,
                'filename' => \basename($filename),
                'hash' => $hash ?? $hashConfig,
            ], $referenceType);
        }

        $configObj = new Config($this->storageManager, $hash, $hashConfig, $config);
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->cacheDir.DIRECTORY_SEPARATOR.'ems_asset_path'.DIRECTORY_SEPARATOR.$hashConfig);
        $cacheFilename = $this->cacheDir.DIRECTORY_SEPARATOR.'ems_asset_path'.DIRECTORY_SEPARATOR.$hashConfig.DIRECTORY_SEPARATOR.$hash;

        if (!$filesystem->exists($cacheFilename)) {
            $stream = $this->processor->getStream($configObj, $filename);
            \file_put_contents($cacheFilename, $stream->getContents());
        }

        return $cacheFilename;
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

    private function fixFileExtension(string $filename, string $mimeType): string
    {
        static $mimetypes = [
            'video/3gpp' => '3gp',
            'application/x-7z-compressed' => '7z',
            'audio/x-aac' => 'aac',
            'audio/x-aiff' => 'aif',
            'video/x-ms-asf' => 'asf',
            'application/atom+xml' => 'atom',
            'video/x-msvideo' => 'avi',
            'image/bmp' => 'bmp',
            'application/x-bzip2' => 'bz2',
            'application/pkix-cert' => 'cer',
            'application/pkix-crl' => 'crl',
            'application/x-x509-ca-cert' => 'crt',
            'text/css' => 'css',
            'text/csv' => 'csv',
            'application/cu-seeme' => 'cu',
            'application/x-debian-package' => 'deb',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/x-dvi' => 'dvi',
            'application/vnd.ms-fontobject' => 'eot',
            'application/epub+zip' => 'epub',
            'text/x-setext' => 'etx',
            'audio/flac' => 'flac',
            'video/x-flv' => 'flv',
            'image/gif' => 'gif',
            'application/gzip' => 'gz',
            'text/html' => 'html',
            'image/x-icon' => 'ico',
            'text/calendar' => 'ics',
            'application/x-iso9660-image' => 'iso',
            'application/java-archive' => 'jar',
            'image/jpeg' => 'jpeg',
            'text/javascript' => 'js',
            'application/json' => 'json',
            'application/x-latex' => 'latex',
            'audio/midi' => 'midi',
            'video/quicktime' => 'mov',
            'video/x-matroska' => 'mkv',
            'audio/mpeg' => 'mp3',
            'video/mp4' => 'mp4',
            'audio/mp4' => 'mp4a',
            'video/mpeg' => 'mpeg',
            'audio/ogg' => 'ogg',
            'video/ogg' => 'ogv',
            'application/ogg' => 'ogx',
            'image/x-portable-bitmap' => 'pbm',
            'application/pdf' => 'pdf',
            'image/x-portable-graymap' => 'pgm',
            'image/png' => 'png',
            'image/x-portable-anymap' => 'pnm',
            'image/x-portable-pixmap' => 'ppm',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-rar-compressed' => 'rar',
            'image/x-cmu-raster' => 'ras',
            'application/rss+xml' => 'rss',
            'application/rtf' => 'rtf',
            'text/sgml' => 'sgml',
            'image/svg+xml' => 'svg',
            'application/x-shockwave-flash' => 'swf',
            'application/x-tar' => 'tar',
            'image/tiff' => 'tiff',
            'application/x-bittorrent' => 'torrent',
            'application/x-font-ttf' => 'ttf',
            'text/plain' => 'txt',
            'audio/x-wav' => 'wav',
            'video/webm' => 'webm',
            'image/webp' => 'webp',
            'audio/x-ms-wma' => 'wma',
            'video/x-ms-wmv' => 'wmv',
            'application/x-font-woff' => 'woff',
            'application/wsdl+xml' => 'wsdl',
            'image/x-xbitmap' => 'xbm',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/xml' => 'xml',
            'image/x-xpixmap' => 'xpm',
            'image/x-xwindowdump' => 'xwd',
            'text/yaml' => 'yml',
            'application/zip' => 'zip',
        ];

        if (!isset($mimetypes[$mimeType])) {
            return $filename;
        }

        if (MimeType::fromFilename($filename) === $mimeType) {
            return $filename;
        }

        return \implode('.', [\pathinfo($filename, PATHINFO_FILENAME), $mimetypes[$mimeType]]);
    }
}
