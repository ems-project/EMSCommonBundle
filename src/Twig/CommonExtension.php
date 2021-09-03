<?php

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Common\Converter;
use EMS\CommonBundle\Common\EMSLink;
use EMS\CommonBundle\Helper\Text\Encoder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CommonExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('ems_asset_path', [AssetRuntime::class, 'assetPath'], ['is_safe' => ['html']]),
            new TwigFunction('ems_unzip', [AssetRuntime::class, 'unzip']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('array_key', [$this, 'arrayKey']),
            new TwigFilter('ems_file_exists', [$this, 'fileExists']),
            new TwigFilter('format_bytes', [Converter::class, 'formatBytes']),
            new TwigFilter('emsch_ouuid', [$this, 'getOuuid']),
            new TwigFilter('locale_attr', [RequestRuntime::class, 'localeAttribute']),
            new TwigFilter('ems_html_encode', [TextRuntime::class, 'htmlEncode'], ['is_safe' => ['html']]),
            new TwigFilter('ems_anti_spam', [TextRuntime::class, 'htmlEncodePii'], ['is_safe' => ['html']]),
            new TwigFilter('ems_manifest', [ManifestRuntime::class, 'manifest']),
            new TwigFilter('ems_json_menu_decode', [TextRuntime::class, 'jsonMenuDecode']),
            new TwigFilter('ems_json_menu_nested_decode', [TextRuntime::class, 'jsonMenuNestedDecode']),
            new TwigFilter('ems_json_decode', [TextRuntime::class, 'jsonDecode']),
            new TwigFilter('ems_webalize', [Encoder::class, 'webalize']),
            new TwigFilter('ems_markdown', [Encoder::class, 'markdownToHtml'], ['is_safe' => ['html']]),
            new TwigFilter('ems_stringify', [Converter::class, 'stringify']),
            new TwigFilter('ems_temp_file', [AssetRuntime::class, 'temporaryFile']),
            new TwigFilter('ems_asset_average_color', [AssetRuntime::class, 'assetAverageColor'], ['is_safe' => ['html']]),
        ];
    }

    public function fileExists(string $filename): bool
    {
        return \file_exists($filename);
    }

    public function arrayKey(array $array, string $key = 'key'): array
    {
        $out = [];

        foreach ($array as $id => $item) {
            if (isset($item[$key])) {
                $out[$item[$key]] = $item;
            } else {
                $out[$id] = $item;
            }
        }

        return $out;
    }

    public function getOuuid(string $emsLink): string
    {
        return EMSLink::fromText($emsLink)->getOuuid();
    }
}
