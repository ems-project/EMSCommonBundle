<?php

namespace EMS\CommonBundle\Storage\Service;

use Exception;

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
    private $connection = null;

    /**
     * @var resource
     */
    private $sftp = null;

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



    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return SftpStorage::class . " ($this->host)";
    }
}
