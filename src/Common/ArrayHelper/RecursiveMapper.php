<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\ArrayHelper;

final class RecursiveMapper
{
    /**
     * Loop recursively overall properties of an associative array and apply mapper.
     *
     * @param array<string, mixed> $data
     *
     * Example:
     *      $data = ['a' => 1, 'b' => '2', 'c' => ['c1' => 3]];
     $      RecursiveMapper::mapPropertiesValues($data, fn (string $property, $value) => ((int) $value * 2));
     *      Will make $data containing ['a' => 2, 'b' => 4, 'c' => ['c1' => 6]]
     */
    public static function mapPropertyValue(array &$data, callable $mapper): void
    {
        foreach ($data as $property => &$value) {
            if (!\is_string($property)) {
                continue;
            }

            if (\is_array($value)) {
                self::mapPropertyValue($value, $mapper);
            } else {
                $value = $mapper($property, $value);
            }
        }
    }
}
