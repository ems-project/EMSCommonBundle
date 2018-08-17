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
        return EMSLink::fromString($emsLink)->getOuuid();
    }
}