<?php

namespace EMS\CommonBundle\Json;

class JsonMenu
{
    /** @var string */
    private $json;
    /** @var string */
    private $glue;
    /** @var array<mixed> */
    private $structure;
    /** @var array<string, string> */
    private $slugs;
    /** @var array<string, mixed> */
    private $bySlugs;
    /** @var array<mixed> */
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

    /**
     * @param array<mixed> $menu
     */
    private function recursiveWalk(array $menu, string $basePath = ''): void
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

    /**
     * @return array<mixed>
     */
    public function getBySlug(string $slug): array
    {
        return $this->bySlugs[$slug] ?? [];
    }

    public function getSlug(string $id): ?string
    {
        return $this->slugs[$id] ?? null;
    }

    /**
     * @return null|array<mixed>
     */
    public function getItem(string $id): ?array
    {
        return $this->items[$id] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function getUids(): array
    {
        return array_keys($this->slugs);
    }

    /**
     * @return array<int, string>
     */
    public function getSlugs(): array
    {
        return array_values($this->slugs);
    }

    public function getJson(): string
    {
        return $this->json;
    }

    /**
     * @return array<mixed>
     */
    public function getStructure(): array
    {
        return $this->structure;
    }

    public function getGlue(): string
    {
        return $this->glue;
    }
}
