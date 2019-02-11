<?php

namespace EMS\CommonBundle\Storage\Service;


use EMS\CommonBundle\Common\HttpClientFactory;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use function in_array;
use function intval;
use Symfony\Component\Routing\Router;
use function urlencode;

class HttpStorage  extends AbstractUrlStorage
{

    /**
     * @var string
     */
    private $baseUrl;
    /**
     * @var string
     */
    private $getUrl;
    /**
     * @var null|string
     */
    private $authKey;


    /**
     * HttpStorage constructor.
     * @param string $baseUrl
     * @param string $getUrl
     * @param null|string $authKey
     */
    public function __construct(string $baseUrl, string $getUrl, ?string $authKey = null)
    {
        $this->baseUrl = $baseUrl;
        $this->getUrl = $getUrl;
        $this->authKey = $authKey;
    }




    /**
     * @inheritdoc
     */
    protected function getBaseUrl(): string
    {
        return $this->baseUrl.$this->getUrl;
    }


    /**
     * @inheritdoc
     */
    protected function getPath($hash, string $cacheContext = null, $confirmed = true, $ds='/'): string
    {
        if($cacheContext)
        {
            return $this->baseUrl.'/asset/'.urlencode($cacheContext).'/'.$hash;
        }
        return $this->baseUrl.$this->getUrl;
    }



    /**
     * @inheritdoc
     */
    public function health(): bool
    {
        try {
            $client = HttpClientFactory::create($this->baseUrl);
            $result = $client->get('/status.json');
            if($result->getStatusCode() == 200)
            {
                $status = json_decode($result->getBody(), true);
                if(isset($status['status']) && in_array($status['status'], ['green', 'yellow']))
                {
                    return TRUE;
                }
            }
        }
        catch(\Exception $e)
        {

        }
        return FALSE;
    }

    /**
     * @inheritdoc
     */
    public function read(string $hash, ?string $cacheContext = null, bool $confirmed=true)
    {
        if ($cacheContext) {
            return false;
        }

        //https://stackoverflow.com/questions/1545432/what-is-the-easiest-way-to-use-the-head-command-of-http-in-php?utm_medium=organic&utm_source=google_rich_qa&utm_campaign=google_rich_qa
        try
        {
            $context = stream_context_create(array('http' => array('method' => 'GET')));
            $fd = fopen($this->baseUrl.$this->getUrl . $hash, 'rb', false, $context);
            return $fd;
        } catch (Exception $e)
        {
            //So it's a FALSE
        }
        return FALSE;
    }


    /**
     * @inheritdoc
     */
    public function supportCacheStore():bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function initUpload(string $hash, int $size, string $name, string $type, ?string $context = null): bool
    {
        try
        {
            $client = HttpClientFactory::create($this->baseUrl);

            $result = $client->post('/api/file/init-upload/'.urlencode($hash).'/'.$size.'?name='.urlencode($name).'&type='.urlencode($type), [
                'headers' => [
                    'X-Auth-Token' => $this->authKey,
                ],

            ]);
            return $result->getStatusCode() === 200;

        }
        catch(\Exception $e)
        {
        }
        return FALSE;
    }

    /**
     * @inheritdoc
     */
    public function addChunk(string $hash, string $chunk, ?string $context = null): bool
    {
        try
        {
            $client = HttpClientFactory::create($this->baseUrl);

            $result = $client->post('/api/file/upload-chunk/'.urlencode($hash), [
                'headers' => [
                    'X-Auth-Token' => $this->authKey,
                ],
                'body' => $chunk,
            ]);
            return $result->getStatusCode() === 200;

        }
        catch(\Exception $e)
        {
        }
        return FALSE;
    }

    /**
     * @inheritdoc
     */
    public function finalizeUpload(string $hash, ?string $context = null): bool
    {
        return $this->head($hash, $context);
    }

    /**
     * @inheritdoc
     */
    public function head(string $hash, ?string $cacheContext = null):bool
    {
        if ($cacheContext) {
            return false;
        }

        //https://stackoverflow.com/questions/1545432/what-is-the-easiest-way-to-use-the-head-command-of-http-in-php?utm_medium=organic&utm_source=google_rich_qa&utm_campaign=google_rich_qa
        try {
            $context = stream_context_create(array('http' => array('method' => 'HEAD')));
            $fd = fopen($this->baseUrl.$this->getUrl . $hash, 'rb', false, $context);
            fclose($fd);
            return TRUE;
        } catch (Exception $e) {
            //So it's a FALSE
        }
        return FALSE;
    }
//
//
//
//    /**
//     * @inheritdoc
//     */
//    public function health(): bool
//    {
//
//        //https://stackoverflow.com/questions/1545432/what-is-the-easiest-way-to-use-the-head-command-of-http-in-php?utm_medium=organic&utm_source=google_rich_qa&utm_campaign=google_rich_qa
//        try {
//            $context = stream_context_create(array('http' => array('method' => 'HEAD')));
//            $fd = fopen($this->postUrl, 'rb', false, $context);
//            fclose($fd);
//            return TRUE;
//        } catch (Exception $e) {
//            //So it's a FALSE
//        }
//        return FALSE;
//    }

    /**
     * @param string $hash
     * @param string $filename
     * @param null|string $cacheContext
     * @return bool
     */
    public function create(string $hash, string $filename, ?string $cacheContext = null):bool
    {
        if ($cacheContext) {
            return false;
        }

        try {

            $client = HttpClientFactory::create($this->baseUrl);
            $client->request('POST', '/api/file', [
                'multipart' => [
                    [
                        'name' => 'upload',
                        'contents' => fopen($filename, 'r'),
                    ]
                ],
                'headers' => [
                    'X-Auth-Token' => $this->authKey,
                ],

            ]);

        } catch (GuzzleException $e) {
            return false;
        }

        return true;
    }
//
//    public function supportCacheStore()
//    {
//        return false;
//    }
//
//    public function read($hash, $cacheContext = false)
//    {
//        if ($cacheContext) {
//            return false;
//        }
//
//        try {
//            //https://stackoverflow.com/questions/3938534/download-file-to-server-from-url?utm_medium=organic&utm_source=google_rich_qa&utm_campaign=google_rich_qa
//            return @fopen($this->getUrl . $hash, 'rb');
//        } catch (Exception $e) {
//            return false;
//        }
//    }

    /**
     * @inheritdoc
     */
    public function getSize(string $hash, ?string $cacheContext = null): ?int
    {
        if ($cacheContext) {
            return false;
        }

        //https://stackoverflow.com/questions/1545432/what-is-the-easiest-way-to-use-the-head-command-of-http-in-php?utm_medium=organic&utm_source=google_rich_qa&utm_campaign=google_rich_qa
        try {
            $context = stream_context_create(array('http' => array('method' => 'HEAD')));
            $fd = fopen($this->baseUrl . $this->getUrl . $hash, 'rb', false, $context);

            $metas = stream_get_meta_data($fd);
            if (isset($metas['wrapper_data'])) {
                foreach ($metas['wrapper_data'] as $meta) {
                    if (preg_match('/^content\-length: (.*)$/i', $meta, $matches, PREG_OFFSET_CAPTURE)) {
                        return intval($matches[1][0]);
                    }
                }
            }
        } catch (Exception $e) {
            //So it's a FALSE
        }
        return null;
    }


    /**
     * @param string $hash
     * @param null|string $context
     * @return \DateTime|null
     */
    public function getLastUpdateDate(string $hash, ?string $context = null): ?\DateTime
    {
        if ($context) {
            return null;
        }

        //https://stackoverflow.com/questions/1545432/what-is-the-easiest-way-to-use-the-head-command-of-http-in-php?utm_medium=organic&utm_source=google_rich_qa&utm_campaign=google_rich_qa
        try {
            $context = stream_context_create(array('http' => array('method' => 'HEAD')));
            $fd = fopen($this->baseUrl.$this->getUrl . $hash, 'rb', false, $context);

            $metas = stream_get_meta_data($fd);
            if (isset($metas['wrapper_data'])) {
                foreach ($metas['wrapper_data'] as $meta) {
                    if (preg_match('/^Last\-Modified: (.*)$/', $meta, $matches, PREG_OFFSET_CAPTURE)) {

                        return \DateTime::createFromFormat('U', strtotime($matches[1][0]));
                    }
                }
            }
        } catch (Exception $e) {
            //So it's a FALSE
        }
        return null;
    }
//
    /**
     * @inheritdoc
     */
    public function __toString():string
    {
        return HttpStorage::class . " ($this->baseUrl)";
    }

//
//
//    /**
//     * @return bool
//     */
//    public function clearCache()
//    {
//        // TODO: should probably be implemented, but how?
//        return false;
//    }
//
//    /**
//     * @param $hash
//     * @return bool
//     */
//    public function remove($hash)
//    {
//        // TODO: should probably be implemented, but how?
//        return false;
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function initUpload(string $hash, ?string $context = null): bool
//    {
//        // TODO: should probably be implemented, but how?
//        return false;
//    }

    /**
     * @param string $hash
     * @return bool
     */
    public function remove(string $hash):bool
    {
        return false;
    }
}
