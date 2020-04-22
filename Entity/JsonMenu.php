<?php

namespace EMS\CommonBundle\Entity;

class JsonMenu
{
    /** @var string */
    private $json;
    /** @var string */
    private $glue;
    /** @var array */
    private $structure;
    /** @var array */
    private $slugs;
    /** @var array */
    private $bySlugs;
    /** @var array */
    private $items;


    public function __construct(string $source, string $glue)
    {
        $this->json = $source;
        $this->glue = $glue;
        $this->structure = json_decode($source, true);
        $this->slugs = [];
        $this->bySlugs = [];
        $this->items = [];
        $this->recursiveWalk($this->structure);
    }

    private function recursiveWalk(array $menu, string $basePath = '')
    {
        foreach ($menu as $item) {
            $slug = $basePath . $item['label'];
            $this->items[$item['id']] = $item;
            $this->slugs[$item['id']] = $slug;
            $this->bySlugs[$slug] = $item;
            if (isset($item['children'])) {
                $this->recursiveWalk($item['children'], $slug . $this->glue);
            }
        }
    }

    public function getBySlug(string $slug): array
    {
        return $this->bySlugs[$slug] ?? [];
    }

    public function getSlug(string $id): ?string
    {
        return $this->slugs[$id] ?? null;
    }

    public function getItem(string $id): ?array
    {
        return $this->items[$id] ?? null;
    }

    public function getUids(): array
    {
        return array_keys($this->slugs);
    }

    public function getSlugs(): array
    {
        return array_values($this->slugs);
    }

    public function getJson(): string
    {
        return $this->json;
    }

    public function getStructure(): array
    {
        return $this->structure;
    }

    public function getGlue(): string
    {
        return $this->glue;
    }
}
