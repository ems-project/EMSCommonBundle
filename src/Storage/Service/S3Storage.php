<?php

namespace EMS\CommonBundle\Storage\Service;

use Aws\S3\S3Client;
use AwsServiceBuilder;
use Psr\Log\LoggerInterface;

class S3Storage extends AbstractUrlStorage
{

    /** @var S3Client*/
    private $s3Client = null;

    /** @var string */
    private $bucket;

    /** @var array{version?:string,credentials?:array{key:string,secret:string},region?:string} */
    private $credentials;

    /**
     * @param array{version?:string,credentials?:array{key:string,secret:string},region?:string} $s3Credentials
     */
    public function __construct(LoggerInterface $logger, array $s3Credentials, string $s3Bucket, int $usage)
    {
        parent::__construct($logger, $usage);
        $this->bucket = $s3Bucket;
        $this->credentials = $s3Credentials;
    }

    protected function getBaseUrl(): string
    {
        if ($this->s3Client === null) {
            $this->s3Client = new S3Client($this->credentials);
            $this->s3Client->registerStreamWrapper();
        }
        return "s3://$this->bucket";
    }

    public function __toString(): string
    {
        return S3Storage::class . " ($this->bucket)";
    }


    public function initUpload(string $hash, int $size, string $name, string $type, int $usageType): bool
    {
        if (!$this->isUsageSupported($usageType)) {
            return false;
        }
        $path = $this->getUploadPath($hash);
        $this->initDirectory($path);
        $result = $this->s3Client->putObject([
            'Bucket'     => $this->bucket,
            'Key'        => substr($path, 1 + strlen($this->getBaseUrl())),
            'Metadata'   => array(
                'Confirmed' => 'false',
            )
        ]);
        return $result->hasKey('ETag');
    }

    public function finalizeUpload(string $hash, int $usageType): bool
    {
        if (!$this->isUsageSupported($usageType)) {
            return false;
        }
        $source = $this->getUploadPath($hash);
        $destination  = $this->getPath($hash);
        \copy($source, $destination);

        $this->s3Client->deleteObject([
            'Bucket'     => $this->bucket,
            'Key'        => substr($source, 1 + strlen($this->getBaseUrl()))
        ]);

        return true;
    }
}
