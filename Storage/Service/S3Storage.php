<?php

namespace EMS\CommonBundle\Storage\Service;

use Aws\Result;
use Aws\S3\S3Client;
use AwsServiceBuilder;
use Exception;
use function strlen;
use function substr;

class S3Storage extends AbstractUrlStorage
{

    /**
     * @var S3Client
     */
    private $s3Client;

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

        $this->s3Client = null;

    }


    /**
     * @inheritdoc
     */
    protected function getBaseUrl(): string
    {
        if($this->s3Client === null)
        {

            $this->s3Client = new S3Client($this->credentials);

//            if($withHealthCheck && !$this->health()){
//                $this->s3Client->createBucket([
//                    'Bucket' => $this->bucket,
//                ]);
//
//                $this->s3Client->waitUntil('BucketExists', ['Bucket' => $this->bucket]);
//            }
            $this->s3Client->registerStreamWrapper();
        }
        return "s3://$this->bucket";
    }


//    private function init($withHealthCheck=true)
//    {
//        if($this->s3Client === null)
//        {
//
//            $this->s3Client = new S3Client($this->credentials);
//
//            if($withHealthCheck && !$this->health()){
//                $this->s3Client->createBucket([
//                    'Bucket' => $this->bucket,
//                ]);
//
//                $this->s3Client->waitUntil('BucketExists', ['Bucket' => $this->bucket]);
//            }
//            $this->s3Client->registerStreamWrapper();
//        }
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function head($hash, $cacheContext = false)
//    {
//        $this->init();
//
//        return $this->s3Client->doesObjectExist($this->bucket, $hash);
//    }
//
//
//    /**
//     * @inheritdoc
//     * @return bool|void
//     */
//    public function supportCacheStore()
//    {
//        $this->init();
//        return true;
//    }
//
//    private function getKey($hash, $context)
//    {
//        return $context?'cache/'.$context.'/'.$hash:$hash;
//    }
//
//    /**
//     * @inheritdoc
//     * @return bool|void
//     */
//    public function remove($hash)
//    {
//        $this->init();
//        // TODO: Implement remove() method.
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function read($hash, $context = false)
//    {
//        $this->init();
//
//        if(!$this->head($hash, $context)) {
//            return false;
//        }
//        $key = $this->getKey($hash, $context);
//
//        return fopen("s3://$this->bucket/$key", 'rb');
//
////        $filename = $this->getCacheFilename($hash, $context);
////
////        $result = $this->s3Client->getObject(array(
////            'Bucket' => $this->bucket,
//////            'LocationConstraint' => $this->region,
////            'Key'    => $context?$context.'/'.$hash:$hash,
//////            'SaveAs' => $filename
////        ));
////
//////        echo($filename);
////        return $result['Body'];
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function create($hash, $filename, $context=null)
//    {
//
//        $this->init();
//        $time = @filemtime($filename);
//
//        $this->s3Client->putObject(array(
//            'Bucket'     => $this->bucket,
////            'LocationConstraint' => $this->region,
//            'Key'        => $this->getKey($hash, $context),
//            'SourceFile' => $filename,
//            'Metadata'   => array(
//                'LastUpdateDate' => $time,
//                'Confirmed' => true,
//            )
//        ));
//
//
//        return $this->read($hash, $context);
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function health(): bool
//    {
//        $this->init(false);
//        try {
//            $this->s3Client->headBucket([
//                'Bucket' => $this->bucket,
//            ]);
//            return true;
//        } catch (Exception $e) {
//            return false;
//        }
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function clearCache()
//    {
//        $this->init();
//        // TODO: Implement clearCache() method.
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function getLastUpdateDate(string $hash, ?string $context = null): ?\DateTime
//    {
//        $this->init();
//        try
//        {
//            $result =  $this->s3Client->headObject([
//                'Bucket'     => $this->bucket,
//                'Key'        => $this->getKey($hash, $context),
//            ]);
//
//            if(isset($result['Metadata']['LastUpdateDate'])){
//
//                return \DateTime::createFromFormat('U', $result['Metadata']['LastUpdateDate']);
//            }
//        }
//        catch (Exception $e)
//        {
//
//        }
//        return null;
//    }
//
//
//
//    /**
//     * @inheritdoc
//     */
//    public function getSize($hash, $context = false)
//    {
//        $this->init();
//        $result =  $this->s3Client->headObject([
//            'Bucket'     => $this->bucket,
//            'Key'        => $this->getKey($hash, $context),
//        ]);
//
//        if(isset($result['ContentLength'])){
//            return $result['ContentLength'];
//        }
//        return null;
//    }
//

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
//
//    /**
//     * @inheritdoc
//     */
//    public function initUpload(string $hash, ?string $context = null): bool
//    {
//
//        $this->init();
//
//        $this->s3Client->putObject(array(
//            'Bucket'     => $this->bucket,
////            'LocationConstraint' => $this->region,
//            'Key'        => $this->getKey($hash, $context),
////            'SourceFile' => $filename,
//            'Metadata'   => array(
//                'Confirmed' => false,
//            )
//        ));
//    }
}