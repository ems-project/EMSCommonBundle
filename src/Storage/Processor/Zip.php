<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Processor;

use Symfony\Component\Filesystem\Filesystem;

class Zip
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function generate(): string
    {
        $filesystem = new Filesystem();

        $tempFile = $filesystem->tempnam(\sys_get_temp_dir(), 'emss');
        $zip = new \ZipArchive();
        $zip->open($tempFile, \ZipArchive::CREATE);

        foreach ($this->config->getFiles() as $file) {
            $zip->addFromString($file['filename'], $file['content']);
        }

        $zip->close();

        return $tempFile;
    }
}
