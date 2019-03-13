<?php

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Helper\Text\Encoder;
use Twig\Extension\RuntimeExtensionInterface;

class TextRuntime implements RuntimeExtensionInterface
{
    /** @var Encoder */
    private $encoder;

    public function __construct(Encoder $encoder)
    {
        $this->encoder = $encoder;
    }

    public function htmlEncode(string $text)
    {
        return $this->encoder->htmlEncode($text);
    }

    public function htmlEncodePii(string $text)
    {
        return $this->encoder->htmlEncodePii($text);
    }
}
