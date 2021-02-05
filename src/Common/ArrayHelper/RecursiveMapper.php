<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common\ArrayHelper;

final class RecursiveMapper
{
    /**
     * @param array<string, mixed> $data
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
