<?php

namespace EMS\CommonBundle\Storage;

use EMS\CommonBundle\Storage\Service\FileSystemStorage;
use EMS\CommonBundle\Storage\Service\HttpStorage;
use EMS\CommonBundle\Storage\Service\S3Storage;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Config\FileLocatorInterface;

class StorageManager
{
    /** @var StorageInterface[] */
    private $adapters = [];

    /** @var FileLocatorInterface */
    private $fileLocator;

    /** @var string */
    private $hashAlgo;

    /**
     * @param array{version?:string,credentials?:array{key:string,secret:string},region?:string} $s3Credentials
     */
    public function __construct(FileLocatorInterface $fileLocator, iterable $adapters, string $hashAlgo, ?string $storagePath, ?string $backendUrl, array $s3Credentials = [], ?string $s3Bucket = null)
    {
        $this->fileLocator = $fileLocator;
        $this->hashAlgo = $hashAlgo;

        foreach ($adapters as $adapter) {
            $this->adapters[] = $adapter;
        }

        if ($storagePath) {
            $this->addAdapter(new FileSystemStorage($storagePath));
        }

        if ($s3Credentials !== null && $s3Bucket !== null) {
            $this->addAdapter(new S3Storage($s3Credentials, $s3Bucket));
        }

        if ($backendUrl) {
            $this->addAdapter(new HttpStorage($backendUrl, '/public/file/'));
        }
    }

    /**
     * @return StorageInterface[]
     */
    public function getAdapters(): iterable
    {
        return $this->adapters;
    }


    public function addAdapter(StorageInterface $storageAdapter): StorageManager
    {
        $this->adapters[] = $storageAdapter;
        return $this;
    }

    public function getStream(string $hash): StreamInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->head($hash)) {
                try {
                    return $adapter->read($hash);
                }
                catch (NotFoundException $e) {
                }
            }
        }
        throw new NotFoundException($hash);
    }

    public function getContents(string $hash): string
    {
        return $this->getStream($hash)->getContents();
    }

    public function getPublicImage(string $name): string
    {
        $file = $this->fileLocator->locate('@EMSCommonBundle/Resources/public/images/' . $name);
        if (is_array($file)) {
            return $file[0] ?? '';
        }
        return $file;
    }

    public function getHashAlgo(): string
    {
        return $this->hashAlgo;
    }

    public function saveContents(string $contents, string $filename, string $mimetype, int $shouldBeSavedOnXServices = 1): string
    {
        $hash = hash($this->hashAlgo, $contents);
        $out = 0;

        /**@var StorageInterface $service */
        foreach ($this->getAdapters() as $service) {
            if ($shouldBeSavedOnXServices != 0 && $out >= $shouldBeSavedOnXServices) {
                break;
            }

            if ($service->head($hash)) {
                ++$out;
                continue;
            }

            if (!$service->initUpload($hash, strlen($contents), $filename, $mimetype)) {
                continue;
            }

            if (!$service->addChunk($hash, $contents)) {
                continue;
            }

            if ($service->finalizeUpload($hash)) {
                ++$out;
            }
        }

        return $hash;
    }

    public function computeStringHash($string): string
    {
        return hash($this->hashAlgo, $string);
    }

    public function computeFileHash($filename): string
    {
        return hash_file($this->hashAlgo, $filename);
    }

    public function initUploadFile(string $fileHash, int $fileSize, string $fileName, string $mimeType, int $uploadMinimumNumberOfReplications): int
    {
        $loopCounter = 0;
        foreach ($this->getAdapters() as $adapter) {
            if ($adapter->initUpload($fileHash, $fileSize, $fileName, $mimeType) && ++$loopCounter >= $uploadMinimumNumberOfReplications) {
                break;
            }
        }
        return $loopCounter;
    }
}
