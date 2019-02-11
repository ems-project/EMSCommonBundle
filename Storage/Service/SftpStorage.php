<?php

namespace EMS\CommonBundle\Storage\Service;


use Exception;
use function file_exists;
use function file_put_contents;
use function ssh2_sftp_stat;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use function fopen;
use function is_resource;
use function ssh2_auth_pubkey_file;
use function ssh2_sftp_unlink;
use function tempnam;
use function tmpfile;

class SftpStorage extends AbstractUrlStorage
{

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $publicKeyFile;

    /**
     * @var string
     */
    private $privateKeyFile;

    /**
     * @var null
     */
    private $passwordPhrase;

    /**
     * @var resource
     */
    private $connection;

    /**
     * @var resource
     */
    private $sftp;

    /**
     * @var bool
     */
    private $contextSupport;

    /**
     * SftpStorage constructor.
     * @param string $host
     * @param string $path
     * @param string $username
     * @param string $publicKeyFile
     * @param string $privateKeyFile
     * @param bool $contextSupport
     * @param null $passwordPhrase
     * @param int $port
     */
    public function __construct(string $host, string $path, string $username, string $publicKeyFile, string $privateKeyFile, bool $contextSupport = false, $passwordPhrase = null, int $port = 22)
    {
        $this->host = $host;
        $this->path = $path;
        $this->port = $port;

        $this->username = $username;
        $this->publicKeyFile = $publicKeyFile;
        $this->privateKeyFile = $privateKeyFile;
        $this->passwordPhrase = $passwordPhrase;

        $this->contextSupport = $contextSupport;

        $this->connection = null;
        $this->sftp = null;
    }



    /**
     * @inheritdoc
     */
    protected function getBaseUrl(): string
    {

        if (!function_exists('ssh2_connect')) {
            throw new Exception("PHP functions Secure Shell are required by $this. (ssh2)");
        }

        if (!$this->connection) {
            $this->connection = @ssh2_connect($this->host, $this->port);
            if (!$this->connection) {
                throw new Exception("Could not connect to $this->host on port $this->port.");
            }
            ssh2_auth_pubkey_file($this->connection, $this->username, $this->publicKeyFile, $this->privateKeyFile, $this->passwordPhrase);
        }

        if (!$this->sftp) {
            $this->sftp = @ssh2_sftp($this->connection);
        }
        return 'ssh2.sftp://' . intval($this->sftp) . $this->path;
    }


//    /**
//     * @param string $hash
//     * @param string $filename
//     * @param bool $cacheContext
//     * @return bool
//     * @throws Exception
//     */
//    public function create($hash, $filename, $cacheContext = false)
//    {
//        if ($cacheContext && !$this->contextSupport) {
//            return false;
//        }
//        $this->init();
//        if (is_resource($this->connection)) {
//            return ssh2_scp_send($this->connection, $filename, $this->getPath($hash, $cacheContext), 0644);
//        }
//        return false;
//    }
//
//    /**
//     * @throws Exception
//     */
//    private function init()
//    {
//
//        if (!function_exists('ssh2_connect')) {
//            throw new Exception("PHP functions Secure Shell are required by $this. (ssh2)");
//        }
//
//        if ($this->connection === false) {
//            $this->connection = @ssh2_connect($this->host, $this->port);
//            if (!$this->connection) {
//                throw new Exception("Could not connect to $this->host on port $this->port.");
//            }
//            ssh2_auth_pubkey_file($this->connection, $this->username, $this->publicKeyFile, $this->privateKeyFile, $this->passwordPhrase);
//        }
//
//        if ($this->sftp === false) {
//            $this->sftp = @ssh2_sftp($this->connection);
//        }
//    }
//
//    /**
//     * @param $hash
//     * @param null $cacheContext
//     * @param boolean $confirmed
//     * @return string
//     * @throws Exception
//     */
//    private function getPath($hash, $cacheContext = null, $confirmed=true)
//    {
//        $out = $this->path;
//
//        if(!$confirmed)
//        {
//            $out = '/uploads';
//        }
//
//        if ($cacheContext) {
//            $out .= '/cache/' . $cacheContext;
//        }
//
//        if($confirmed)
//        {
//            $out .= '/' . substr($hash, 0, 3);
//        }
//
//        $this->init();
//        if (is_resource($this->sftp)) {
//            @ssh2_sftp_mkdir($this->sftp, $out, 0777, true);
//        } else {
//            throw new Exception('EMS was not able to initiate the sftp connection');
//        }
//
//        return $out . DIRECTORY_SEPARATOR . $hash;
//    }
//
//    /**
//     * @return bool
//     */
//    public function supportCacheStore()
//    {
//        return $this->contextSupport;
//    }
//
//    /**
//     * @param string $hash
//     * @param bool|string $cacheContext
//     * @param bool $confirmed
//     * @return resource|bool
//     */
//    public function read($hash, $cacheContext = false, $confirmed=true)
//    {
//        if ($cacheContext && !$this->contextSupport) {
//            return false;
//        }
//        $this->init();
//        return @fopen('ssh2.sftp://' . intval($this->sftp) . $this->getPath($hash, $cacheContext), 'r');
//    }
//
//
//
//
//    /**
//     * @inheritdoc
//     */
//    public function health(): bool
//    {
//        try {
//            @filemtime('ssh2.sftp://' . intval($this->sftp) . '~');
//            return TRUE;
//        } catch (Exception $e) {
//            //So it's a FALSE
//        }
//        return FALSE;
//    }
//
//    /**
//     * @param string $hash
//     * @param bool $cacheContext
//     * @return bool|int
//     * @throws Exception
//     */
//    public function getLastUpdateDate(string $hash, ?string $context = null): ?\DateTime
//    {
//        if ($context && !$this->contextSupport) {
//            return false;
//        }
//        $this->init();
//
//        if (is_resource($this->sftp)) {
//            $time = @filemtime('ssh2.sftp://' . intval($this->sftp) . $this->getPath($hash, $context));
//            return $time ? \DateTime::createFromFormat('U', $time) : null;
//        }
//        return false;
//    }
//
//    /**
//     * @param string $hash
//     * @param bool $cacheContext
//     * @return bool|int
//     * @throws Exception
//     */
//    public function getSize($hash, $cacheContext = false)
//    {
//        if ($cacheContext && !$this->contextSupport) {
//            return false;
//        }
//        $this->init();
//
//        if (is_resource($this->sftp)) {
//
//            return filesize('ssh2.sftp://' . intval($this->sftp) . $this->getPath($hash, $cacheContext));
//        }
//        return false;
//    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return SftpStorage::class . " ($this->host)";
    }

//    /**
//     * @inheritdoc
//     */
//    protected function supportAppend() {
//        return false;
//    }

//    /**
//     * @return bool
//     * @throws Exception
//     */
//    public function clearCache()
//    {
//        $this->init();
//        $fileSystem = new Filesystem();
//        $fileSystem->remove('ssh2.sftp://' . intval($this->sftp) . $this->path . DIRECTORY_SEPARATOR . 'cache');
//        return false;
//    }
//
//    /**
//     * @param $hash
//     * @return bool
//     * @throws Exception
//     */
//    public function remove($hash)
//    {
//        if ($this->head($hash)) {
//            if (is_resource($this->sftp)) {
//                ssh2_sftp_unlink($this->sftp, $this->getPath($hash));
//            }
//        }
//        $finder = new Finder();
//        $finder->name($hash);
//        foreach ($finder->in('ssh2.sftp://' . intval($this->sftp) . $this->path . DIRECTORY_SEPARATOR . 'cache') as $file) {
//            if (is_resource($this->sftp)) {
//                ssh2_sftp_unlink($this->sftp, $file);
//            }
//        }
//        return true;
//    }
//
//    /**
//     * @param string $hash
//     * @param bool $cacheContext
//     * @return bool
//     * @throws Exception
//     */
//    public function head($hash, $cacheContext = false)
//    {
//        if ($cacheContext && !$this->contextSupport) {
//            return false;
//        }
//        $this->init();
//        try {
//            if ($this->sftp && is_resource($this->sftp)) {
//                if (is_resource($this->sftp)) {
//                    return @file_exists('ssh2.sftp://' . intval($this->sftp) . $this->getPath($hash, $cacheContext));
//                }
//            }
//        } catch (Exception $e) {
//        }
//        return false;
//    }
//
//
//
//    /**
//     * @inheritdoc
//     */
//    public function initUpload(string $hash, int $size, string $name, string $type, ?string $context = null): bool
//    {
//        $this->init();
//        $path = $this->getPath($hash, $context, false);
//        $emptyFile = tempnam(sys_get_temp_dir(), 'EMPTY');
//        file_put_contents($emptyFile, '');
//        return ssh2_scp_send($this->connection, $emptyFile, $path, 0644);
//    }
//
//    /**
//     * @param string      $hash
//     * @param string      $chunk
//     * @param string|null $context
//     *
//     * @return bool
//     */
//    public function addChunk(string $hash, string $chunk, ?string $context = null): bool
//    {
//        $this->init();
//        $path = $this->path.'/uploads';
////        if(!file_exists($path)) {
////            throw new NotFoundHttpException('temporary file not found');
////        }
//
//        $myFile = fopen('ssh2.sftp://' . intval($this->sftp) . $path, "a");
//        $result = (fwrite($myFile, $chunk) !== FALSE);
//        fflush($myFile);
//        fclose($myFile);
//        return $result;
//    }
//
//    /**
//     * @param string      $hash
//     * @param string|null $context
//     *
//     * @return bool
//     */
//    public function finalizeUpload(string $hash, ?string $context = null): bool
//    {
//        $this->init();
//        return copy('ssh2.sftp://' . intval($this->sftp) . $this->getPath($hash, $context, false), 'ssh2.sftp://' . intval($this->sftp) . $this->getPath($hash, $context));
//    }
}
