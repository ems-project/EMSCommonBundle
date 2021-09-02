<?php

namespace EMS\CommonBundle\Tests\Unit\Storage;

use EMS\CommonBundle\Storage\Factory\FileSystemFactory;
use EMS\CommonBundle\Storage\Service\StorageInterface;
use EMS\CommonBundle\Storage\StorageManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Config\FileLocator;

class StorageManagerTest extends WebTestCase
{
    private const BAR = 'bar';
    private const FOO = 'foo';
    private StorageManager $storageManager;
    private string $tempFile;
    private string $hash;

    protected function setUp(): void
    {
        $fileLocator = new FileLocator();
        $mockLogger = $this->createMock(LoggerInterface::class);
        $fsFactory = new FileSystemFactory($mockLogger, \tempnam(\sys_get_temp_dir(), 'StorageManagerTest'), \sys_get_temp_dir());
        $fsDir1 = \tempnam(\sys_get_temp_dir(), 'StorageManagerTest');
        \unlink($fsDir1);
        \mkdir($fsDir1);
        $fsDir2 = \tempnam(\sys_get_temp_dir(), 'StorageManagerTest');
        \unlink($fsDir2);
        \mkdir($fsDir2);
        $fsDir3 = \tempnam(\sys_get_temp_dir(), 'StorageManagerTest');
        \unlink($fsDir3);
        \mkdir($fsDir3);
        $this->storageManager = new StorageManager($fileLocator, [$fsFactory], 'sha1', [[
            'type' => 'fs',
            'path' => $fsDir1,
        ], [
            'type' => 'fs',
            'path' => $fsDir2,
        ], [
            'type' => 'fs',
            'path' => $fsDir3,
            'usage' => StorageInterface::STORAGE_USAGE_ASSET_ATTRIBUTE,
        ]]);

        $this->tempFile = \tempnam(\sys_get_temp_dir(), 'StorageManagerTest');
        \file_put_contents($this->tempFile, self::FOO.self::BAR);

        $this->hash = \sha1(self::FOO.self::BAR);
    }

    public function testHash(): void
    {
        $this->assertEquals('sha1', $this->storageManager->getHashAlgo());
        $this->assertEquals($this->hash, $this->storageManager->computeStringHash(self::FOO.self::BAR));
        $this->assertEquals($this->hash, $this->storageManager->computeFileHash($this->tempFile));
    }

    public function testHealthStatuses(): void
    {
        foreach ($this->storageManager->getHealthStatuses() as $status) {
            $this->assertTrue($status);
        }
    }

    public function testFoobarFileByChunkUpload(): void
    {
        $this->assertFalse($this->storageManager->head($this->hash));

        $size = \strlen(self::FOO.self::BAR);
        $this->assertGreaterThanOrEqual(1, $this->storageManager->initUploadFile($this->hash, $size, 'test.bin', 'application/bin', StorageInterface::STORAGE_USAGE_CACHE));
        $this->assertGreaterThanOrEqual(1, $this->storageManager->addChunk($this->hash, self::FOO, StorageInterface::STORAGE_USAGE_CACHE));
        $this->assertGreaterThanOrEqual(1, $this->storageManager->addChunk($this->hash, self::BAR, StorageInterface::STORAGE_USAGE_CACHE));
        $this->assertGreaterThanOrEqual(1, $this->storageManager->finalizeUpload($this->hash, $size, StorageInterface::STORAGE_USAGE_CACHE));

        $this->assertTrue($this->storageManager->head($this->hash));

        $ctx = \hash_init($this->storageManager->getHashAlgo());
        $stream = $this->storageManager->getStream($this->hash);
        $this->assertNotNull($stream);
        while (!$stream->eof()) {
            \hash_update($ctx, $stream->read(8192));
        }
        $computedHash = \hash_final($ctx);

        $this->assertEquals($this->hash, $computedHash);
        $this->assertEquals(2, \count($this->storageManager->headIn($this->hash)));

        $this->assertEquals(3, $this->storageManager->remove($this->hash));

        $this->assertFalse($this->storageManager->head($this->hash));
    }

    public function testFoobarFileBySingleUpload(): void
    {
        $tempFile = \tempnam(\sys_get_temp_dir(), 'ems_core_test');
        if (!\is_string($tempFile)) {
            throw new \RuntimeException('Impossible to generate temporary filename');
        }
        $this->assertNotFalse(false !== $tempFile);
        $this->assertNotFalse(false !== \file_put_contents($tempFile, self::FOO.self::BAR));
        $this->assertEquals($this->hash, \hash_file($this->storageManager->getHashAlgo(), $tempFile));

        $hashAfterSave = $this->storageManager->saveFile($tempFile, StorageInterface::STORAGE_USAGE_ASSET);
        $this->assertEquals($this->hash, $hashAfterSave);
        $this->assertTrue($this->storageManager->head($this->hash));
        $this->assertEquals($this->hash, \hash($this->storageManager->getHashAlgo(), $this->storageManager->getContents($this->hash)));

        $this->assertEquals(\strlen(self::FOO.self::BAR), $this->storageManager->getSize($this->hash));
        $this->assertEquals(\base64_encode(self::FOO.self::BAR), $this->storageManager->getBase64($this->hash));

        $this->assertEquals(3, \count($this->storageManager->headIn($this->hash)));

        $this->assertEquals(3, $this->storageManager->remove($this->hash));
    }

    public function testSaveConfig(): void
    {
        $data = [
            self::FOO => self::BAR,
        ];

        $configHash = $this->storageManager->saveConfig($data);
        $this->assertEquals(\sha1(\json_encode($data)), $configHash);
        $this->assertEquals(2, \count($this->storageManager->headIn($configHash)));
        $this->assertEquals(3, $this->storageManager->remove($configHash));
    }
}
