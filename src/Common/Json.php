<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common;

final class Json
{
    /**
     * @param mixed $value
     */
    public static function encode($value, bool $pretty = false): string
    {
        $options = $pretty ? JSON_PRETTY_PRINT : 0;
        $encoded = \json_encode($value, $options);

        if (false === $encoded) {
            throw new \RuntimeException('failed encoding json');
        }

        return $encoded;
    }

    /**
     * @return array<mixed>
     */
    public static function decode(string $value): array
    {
        $decoded = \json_decode($value, true);

        if (JSON_ERROR_NONE !== \json_last_error() || !\is_array($decoded)) {
            throw new \RuntimeException(\sprintf('Invalid json %s', \json_last_error_msg()));
        }

        return $decoded;
    }
}
