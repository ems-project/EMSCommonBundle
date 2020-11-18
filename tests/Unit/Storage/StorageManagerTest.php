<?php

namespace EMS\CommonBundle\Tests\Unit\Storage;

use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StorageManagerTest extends WebTestCase
{
    public function testStorageServices(): void
    {
        self::bootKernel();

        $this->assertNotNull(self::$container);

        $storageManager = self::$container->get('ems_common.storage.manager');

        if (!$storageManager instanceof StorageManager) {
            throw new \RuntimeException('StorageManager not found');
        }

        $this->verifyStorageManager($storageManager);
    }

    private function verifyStorageManager(StorageManager $storage): void
    {
        foreach ($storage->getHealthStatuses() as $status) {
            $this->assertTrue($status);
        }

        $string1 = 'foo';
        $string2 = 'bar';
        $hash = \hash($storage->getHashAlgo(), $string1 . $string2);
        if ($storage->head($hash)) {
            $storage->remove($hash);
        }

        $size = \strlen($string1 . $string2);
        $this->assertGreaterThanOrEqual(1, $storage->initUploadFile($hash, $size, 'test.bin', 'application/bin'));
        $this->assertGreaterThanOrEqual(1, $storage->addChunk($hash, $string1));
        $this->assertGreaterThanOrEqual(1, $storage->addChunk($hash, $string2));
        $this->assertGreaterThanOrEqual(1, $storage->finalizeUpload($hash, $size));


        $this->assertTrue($storage->head($hash));

        $ctx = \hash_init($storage->getHashAlgo());
        $stream = $storage->getStream($hash);
        $this->assertNotNull($stream);
        while (!$stream->eof()) {
            \hash_update($ctx, $stream->read(8192));
        }
        $computedHash = \hash_final($ctx);

        $this->assertEquals($hash, $computedHash);

        if ($storage->remove($hash)) {
            $this->assertFalse($storage->head($hash));
        }


        $tempFile = \tempnam(sys_get_temp_dir(), 'ems_core_test');
        if (!\is_string($tempFile)) {
            throw new \RuntimeException('Impossible to generate temporary filename');
        }
        $this->assertNotFalse($tempFile !== false);
        $this->assertNotFalse(file_put_contents($tempFile, $string1 . $string2) !== false);
        $this->assertEquals($hash, \hash_file($storage->getHashAlgo(), $tempFile));

        $hashAfterSave = $storage->saveFile($tempFile);
        $this->assertEquals($hash, $hashAfterSave);
        $this->assertTrue($storage->head($hash));

        $this->assertEquals(strlen($string1 . $string2), $storage->getSize($hash));

        $storage->remove($hash);
        unlink($tempFile);
    }
}
