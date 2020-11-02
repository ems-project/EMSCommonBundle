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


    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $out = parent::getFunctions();
        $out[] = new TwigFunction('ems_asset_path', [RequestRuntime::class, 'assetPath'], ['is_safe' => ['html']]);
        return $out;
    }

    /**
     * {@inheritdoc}
     */
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
        ];
    }

    public function fileExists(string $filename): bool
    {
        return file_exists($filename);
    }

    /**
     * @param array  $array
     * @param string $key
     *
     * @return array
     */
    public function arrayKey(array $array, $key = 'key'): array
    {
        $out = [];

        foreach ($array as $id => $item) {
            if (isset($item[$key])) {
                $out[$item[$key]] =  $item;
            } else {
                $out[$id] =  $item;
            }
        }

        return $out;
    }

    /**
     * @param string $emsLink
     *
     * @return string
     */
    public function getOuuid(string $emsLink): string
    {
        return EMSLink::fromText($emsLink)->getOuuid();
    }
}
