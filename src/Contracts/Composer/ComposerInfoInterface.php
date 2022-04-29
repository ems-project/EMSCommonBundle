<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Composer;

interface ComposerInfoInterface
{
    public const PACKAGES = [
        'elasticms/core-bundle' => 'core',
        'elasticms/client-helper-bundle' => 'client',
        'elasticms/common-bundle' => 'common',
        'elasticms/form-bundle' => 'form',
        'elasticms/submission-bundle' => 'submission',
        'symfony/framework-bundle' => 'symfony',
    ];

    public function getVersionPackages(): array;
}