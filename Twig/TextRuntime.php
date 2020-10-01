<?php

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Json\Decoder;
use EMS\CommonBundle\Helper\Text\Encoder;
use EMS\CommonBundle\Json\JsonMenuNested;
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

    public function webalize(string $text) : ?string
    {
        return $this->encoder->webalize($text);
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
        return $this->decoder->jsonMenuDecode($json, $glue);
    }

    public function jsonMenuNestedDecode(string $json): JsonMenuNested
    {
        return $this->decoder->jsonMenuNestedDecode($json);
    }

    public function jsonDecode(string $json, bool $assoc = true, int $depth = 512, int $options = 0)
    {
        return \json_decode($json, $assoc, $depth, $options);
    }
}
