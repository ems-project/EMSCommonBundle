<?php

namespace EMS\CommonBundle\Twig;

use Twig\Extension\RuntimeExtensionInterface;

class ManifestRuntime implements RuntimeExtensionInterface
{
    public function manifest(string $manifestUrl, string $resource): string
    {
        $url = parse_url($manifestUrl);
        $manifest = \json_decode(file_get_contents($manifestUrl), true);

        if (isset($manifest[$resource])) {
            return sprintf('%s://%s/%s', $url['scheme'], $url['host'], $manifest[$resource]);
        }

        return $manifestUrl;
    }
}
