<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\Composer;

use EMS\CommonBundle\Common\Standard\Json;
use EMS\CommonBundle\Contracts\Composer\ComposerInfoInterface;

final class ComposerInfo implements ComposerInfoInterface
{
    private string $projectDir;
    /** @var array<string, string> */
    private array $versionPackages = [];

    public function __construct(string $rootDir)
    {
        $this->projectDir = $rootDir;
    }

    /**
     * @return array<string, string>
     */
    public function getVersionPackages(): array
    {
        return $this->versionPackages;
    }

    /**
     * @return array<mixed>
     */
    public function build(): void
    {
        $path = $this->projectDir.DIRECTORY_SEPARATOR.'composer.lock';
        $composerLockFile = Json::decodeFile($path);

        $allPackages = $composerLockFile['packages'] ?? [];
        $packages = \array_filter($allPackages, fn (array $p) => \array_key_exists($p['name'], self::PACKAGES));

        foreach ($packages as $p) {
            $shortname = self::PACKAGES[$p['name']];
            $this->versionPackages[$shortname] = $p['version'];
        }
    }
}
