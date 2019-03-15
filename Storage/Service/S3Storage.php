<?php

namespace EMS\CommonBundle\Storage\Service;

use Aws\S3\S3Client;
use AwsServiceBuilder;

class S3Storage extends AbstractUrlStorage
{

    /**
     * @var S3Client
     */
    private $s3Client = null;

    /**
     * @var string
     */
    private $bucket;

    /**
     * @var array
     */
    private $credentials;

    public function __construct(array $s3Credentials, string $s3Bucket)
    {
        $this->bucket = $s3Bucket;
        $this->credentials = $s3Credentials;
    }


    /**
     * @inheritdoc
     */
    protected function getBaseUrl(): string
    {
        if ($this->s3Client === null) {
            $this->s3Client = new S3Client($this->credentials);
            $this->s3Client->registerStreamWrapper();
        }
        return "s3://$this->bucket";
    }


    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return S3Storage::class . " ($this->bucket)";
    }


    /**
     * @param string $hash
     * @param int $size
     * @param string $name
     * @param string $type
     * @param null|string $context
     * @return bool
     */
    public function initUpload(string $hash, int $size, string $name, string $type, ?string $context = null): bool
    {
        $path = $this->getPath($hash, $context, false);
        $result = $this->s3Client->putObject([
            'Bucket'     => $this->bucket,
            'Key'        => substr($path, 1+strlen($this->getBaseUrl())),
            'Metadata'   => array(
                'Confirmed' => 'false',
            )
        ]);
        return $result->hasKey('ETag');
    }



    /**
     * @param string      $hash
     * @param string|null $context
     *
     * @return bool
     */
    public function finalizeUpload(string $hash, ?string $context = null): bool
    {
        $out = parent::finalizeUpload($hash, $context);
        $path = $this->getPath($hash, $context, false);
        $this->s3Client->deleteObject([
            'Bucket'     => $this->bucket,
            'Key'        => substr($path, 1+strlen($this->getBaseUrl()))
        ]);
        return $out;
    }
}
