<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Storage\StorageManager;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use ZipArchive;

class AssetRuntime
{
    /** @var StorageManager */
    private $storageManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $publicDir;
    /** @var Filesystem */
    private $filesystem;

    public function __construct(StorageManager $storageManager, LoggerInterface $logger, string $projectDir)
    {
        $this->storageManager = $storageManager;
        $this->logger = $logger;
        $this->publicDir = $projectDir.'/public';
        $this->filesystem = new Filesystem();
    }

    /**
     * @return array<int, SplFileInfo>
     */
    public function unzip(string $hash, string $saveDir, bool $mergeContent = false): array
    {
        try {
            $checkFilename = $saveDir.\DIRECTORY_SEPARATOR.$this->storageManager->computeStringHash($saveDir);
            $checkHash = \file_exists($checkFilename) ? \file_get_contents($checkFilename) : false;

            if ($checkHash !== $hash) {
                if (!$mergeContent && $this->filesystem->exists($saveDir)) {
                    $this->filesystem->remove($saveDir);
                }

                $this::extract($this->storageManager->getStream($hash), $saveDir);
                \file_put_contents($checkFilename, $hash);
            }

            $excludeCheckFile = function (SplFileInfo $f) use ($checkFilename) {
                return $f->getPathname() !== $checkFilename;
            };

            return \iterator_to_array(Finder::create()->in($saveDir)->files()->filter($excludeCheckFile)->getIterator());
        } catch (\Exception $e) {
            $this->logger->error('ems_zip failed : {error}', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }

        return [];
    }

    public static function temporaryFile(StreamInterface $stream): ?string
    {
        $path = \tempnam(\sys_get_temp_dir(), 'emsch');
        if (!$path) {
            throw new \RuntimeException(\sprintf('Could not create temp file in %s', \sys_get_temp_dir()));
        }
        \file_put_contents($path, $stream->getContents());

        return $path;
    }

    public static function extract(StreamInterface $stream, string $destination): bool
    {
        $path = self::temporaryFile($stream);

        $zip = new ZipArchive();
        if (true !== $open = $zip->open($path)) {
            throw new \RuntimeException(\sprintf('Failed opening zip %s (ZipArchive %s)', $path, $open));
        }

        if (!$zip->extractTo($destination)) {
            throw new \RuntimeException(\sprintf('Extracting of zip file failed (%s)', $destination));
        }

        $zip->close();

        return true;
    }
}
