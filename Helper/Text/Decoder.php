<?php

namespace EMS\CommonBundle\Helper\Text;

use EMS\CommonBundle\Entity\JsonMenu;

class Decoder
{
    public function jsonMenuDecode(string $text, string $glue): JsonMenu
    {
        return new JsonMenu($text, $glue);
    }
}
