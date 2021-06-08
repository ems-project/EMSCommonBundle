<?php

namespace EMS\CommonBundle\Json;

class Decoder
{
    public function jsonMenuDecode(string $text, string $glue): JsonMenu
    {
        return new JsonMenu($text, $glue);
    }

    public function jsonMenuNestedDecode(string $json): JsonMenuNested
    {
        return JsonMenuNested::fromStructure($json);
    }

    /**
     * @param array<mixed> $menu
     *
     * @return iterable<array{level: int, data: array<mixed>, index: int|string}>
     */
    public function jsonWalk(array &$menu, string $childrenFiledName): iterable
    {
        yield from $this->jsonWalkHidden($menu, $childrenFiledName, 0);
    }

    /**
     * @param array<mixed> $menu
     *
     * @return iterable<array{level: int, data: array<mixed>, index: int|string}>
     */
    private function jsonWalkHidden(array &$menu, string $childrenFiledName, int $level): iterable
    {
        foreach ($menu as $index => $data) {
            yield ['level' => $level, 'data' => $data, 'index' => $index];
            if (\is_array($data[$childrenFiledName] ?? null)) {
                yield from $this->jsonWalkHidden($data[$childrenFiledName], $childrenFiledName, $level + 1);
            }
        }
    }
}
