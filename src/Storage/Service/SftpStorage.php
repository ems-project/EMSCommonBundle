<?php

namespace EMS\CommonBundle\Storage\Service;

class SftpStorage extends AbstractUrlStorage
{
    /** @var string */
    private $host;
    /** @var string */
    private $path;
    /** @var int */
    private $port;
    /** @var string */
    private $username;
    /** @var string */
    private $publicKeyFile;
    /** @var string */
    private $privateKeyFile;
    /** @var null */
    private $passwordPhrase;
    /** @var resource|null */
    private $sftp = null;
    /** @var bool */
    private $contextSupport;

    /**
     * @param null $passwordPhrase
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

    protected function getBaseUrl(): string
    {
        if ($this->sftp === null) {
            $this->connect();
        }
        return 'ssh2.sftp://' . intval($this->sftp) . $this->path;
    }

    private function connect(): void
    {
        if (!function_exists('ssh2_connect')) {
            throw new \Exception("PHP functions Secure Shell are required by $this. (ssh2)");
        }

        $connection = @ssh2_connect($this->host, $this->port);
        if ($connection === false) {
            throw new \Exception("Could not connect to $this->host on port $this->port.");
        }

        if ($this->passwordPhrase === null) {
            ssh2_auth_pubkey_file($connection, $this->username, $this->publicKeyFile, $this->privateKeyFile);
        } else {
            ssh2_auth_pubkey_file($connection, $this->username, $this->publicKeyFile, $this->privateKeyFile, $this->passwordPhrase);
        }

        $sftp = @ssh2_sftp($connection);
        if ($sftp === false) {
            throw new \Exception("Could not initialize SFTP subsystem to $this->host");
        }

        $this->sftp = $sftp;
    }

    public function __toString(): string
    {
        return SftpStorage::class . " ($this->host)";
    }
}
