<?php

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Helper\Text\Decoder;
use EMS\CommonBundle\Helper\Text\Encoder;
use Twig\Extension\RuntimeExtensionInterface;

class TextRuntime implements RuntimeExtensionInterface
{
    /** @var Encoder */
    private $encoder;
    /** @var Decoder */
    private $decoder;

    public function __construct(Encoder $encoder, Decoder $decoder)
    {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
    }

    public function htmlEncode(string $text)
    {
        return $this->encoder->htmlEncode($text);
    }

    public function htmlEncodePii(string $text)
    {
        return $this->encoder->htmlEncodePii($text);
    }

    public function jsonMenuDecode(string $json, string $glue = '/')
    {
        return$this->decoder->jsonMenuDecode($json, $glue);
    }
}
