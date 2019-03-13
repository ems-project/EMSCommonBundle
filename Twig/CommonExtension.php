<?php

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Common\Converter;
use EMS\CommonBundle\Common\EMSLink;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CommonExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('array_key', [$this, 'arrayKey']),
            new TwigFilter('format_bytes', [Converter::class, 'formatBytes']),
            new TwigFilter('emsch_ouuid', [$this, 'getOuuid']),
            new TwigFilter('locale_attr', [RequestRuntime::class, 'localeAttribute']),
            new TwigFilter('ems_html_encode', [TextRuntime::class, 'html_encode'], ['is_safe' => ['html']]),
            new TwigFilter('ems_anti_spam', [TextRuntime::class, 'html_encode_pii'], ['is_safe' => ['html']]),
        ];
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
