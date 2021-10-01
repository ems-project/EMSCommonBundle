<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Json;

use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @implements \IteratorAggregate<JsonMenuNested>
 */
final class JsonMenuNested implements \IteratorAggregate
{
    /** @var string */
    private $id;
    /** @var string */
    private $type;
    /** @var string */
    private $label;
    /** @var array<mixed> */
    private $object;
    /** @var JsonMenuNested[] */
    private $children = [];
    /** @var JsonMenuNested|null */
    private $parent;
    /** @var string[] */
    private array $descendantIds;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->label = $data['label'] ?? '';
        $this->object = $data['object'] ?? [];

        $children = $data['children'] ?? [];

        $this->descendantIds = [];
        foreach ($children as $child) {
            $childItem = new JsonMenuNested($child);
            $childItem->setParent($this);
            $this->descendantIds = \array_merge($this->descendantIds, [$childItem->getId()], $childItem->getDescendantIds());

            $this->children[] = $childItem;
        }
    }

    public static function fromStructure(string $structure): JsonMenuNested
    {
        return new self([
           'id' => 'root',
           'type' => 'root',
           'label' => 'root',
           'children' => \json_decode($structure, true),
        ]);
    }

    public function __toString()
    {
        return $this->label;
    }

    /**
     * Return a flat array.
     *
     * @return array<JsonMenuNested>
     */
    public function toArray(): array
    {
        $data = [$this];

        foreach ($this->children as $child) {
            $data = \array_merge($data, $child->toArray());
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArrayStructure(bool $includeRoot = false): array
    {
        $children = $this->children;
        $structureChildren = \array_map(fn (JsonMenuNested $c) => $c->toArrayStructure(true), $children);

        if (!$includeRoot) {
            return $structureChildren;
        }

        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'object' => $this->object,
            'children' => \array_map(fn (JsonMenuNested $c) => $c->toArrayStructure(true), $children),
        ];
    }

    /**
     * @return iterable<JsonMenuNested>|JsonMenuNested[]
     */
    public function getIterator(): iterable
    {
        foreach ($this->children as $child) {
            yield $child;

            if ($child->hasChildren()) {
                yield from $child;
            }
        }
    }

    /**
     * @return iterable<JsonMenuNested>|JsonMenuNested[]
     */
    public function search(string $propertyPath, string $value, ?string $type = null): iterable
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->getIterator() as $child) {
            if (null !== $type && $child->getType() !== $type) {
                continue;
            }

            if (!$propertyAccessor->isReadable($child->getObject(), $propertyPath)) {
                continue;
            }

            $objectValue = $propertyAccessor->getValue($child->getObject(), $propertyPath);

            if ($objectValue === $value) {
                yield $child;
            }
        }
    }

    public function getItemById(string $id): ?JsonMenuNested
    {
        foreach ($this->getIterator() as $child) {
            if ($child->getId() === $id) {
                return $child;
            }
        }

        return null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return array<mixed>
     */
    public function getObject(): array
    {
        return $this->object;
    }

    /**
     * @param JsonMenuNested[] $children
     */
    public function setChildren(array $children): void
    {
        $this->children = $children;
    }

    /**
     * @return JsonMenuNested[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @return JsonMenuNested[]
     */
    public function getPath(): array
    {
        $path = [$this];

        if (null !== $this->parent && !$this->parent->isRoot()) {
            $path = \array_merge($this->parent->getPath(), $path);
        }

        return $path;
    }

    public function getParent(): ?JsonMenuNested
    {
        return $this->parent;
    }

    public function hasChildren(): bool
    {
        return \count($this->children) > 0;
    }

    public function isRoot(): bool
    {
        return null === $this->parent;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @param array<string, mixed> $object
     */
    public function setObject(array $object): void
    {
        $this->object = $object;
    }

    public function setParent(?JsonMenuNested $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return string[]
     */
    public function getDescendantIds(): array
    {
        return $this->descendantIds;
    }

    /**
     * @return iterable<JsonMenuNested>
     */
    public function breadcrumb(string $uid, bool $reverseOrder = false): iterable
    {
        yield from $this->yieldBreadcrumb($uid, $this->children, $reverseOrder);
    }

    /**
     * @param JsonMenuNested[] $menu
     *
     * @return iterable<JsonMenuNested>
     */
    private function yieldBreadcrumb(string $uid, array $menu, bool $reverseOrder): iterable
    {
        foreach ($menu as $item) {
            if ($item->getId() === $uid) {
                yield $item;
                break;
            }
            if (\in_array($uid, $item->getDescendantIds())) {
                if (!$reverseOrder) {
                    yield $item;
                }
                yield from $this->yieldBreadcrumb($uid, $item->getChildren(), $reverseOrder);
                if ($reverseOrder) {
                    yield $item;
                }
                break;
            }
        }
    }
}
