<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Composer;

use EMS\CommonBundle\Common\Standard\Json;
use EMS\CommonBundle\Contracts\Composer\ComposerInfoInterface;

final class ComposerInfo implements ComposerInfoInterface
{
    private string $projectDir;
    /** @var ?array<mixed> */
    private ?array $composerLockFile = null;

    public function __construct(string $rootDir)
    {
        $this->projectDir = $rootDir;
    }

    /**
     * @return array<string, string>
     */
    public function getVersionPackages(): array
    {
        $composerLockFile = $this->getComposerLockFile();
        $allPackages = $composerLockFile['packages'] ?? [];
        $packages = \array_filter($allPackages, fn (array $p) => \array_key_exists($p['name'], self::PACKAGES));

        $versionPackages = [];

        foreach ($packages as $p) {
            $shortname = self::PACKAGES[$p['name']];
            $versionPackages[$shortname] = $p['version'];
        }

        return $versionPackages;
    }

    /**
     * @return array<mixed>
     */
    private function getComposerLockFile(): array
    {
        return $this->composerLockFile ?: $this->createComposerLockFile();
    }

    /**
     * @return array<mixed>
     */
    private function createComposerLockFile(): array
    {
        $path = $this->projectDir.DIRECTORY_SEPARATOR.'composer.lock';
        $this->composerLockFile = Json::decodeFile($path);

        return $this->composerLockFile;
    }
}
