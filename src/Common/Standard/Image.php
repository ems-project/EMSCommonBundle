<?php

namespace EMS\CommonBundle\Common\Standard;

final class Image
{
    /**
     * @return resource
     */
    public static function imageCreateFromString(string $resource)
    {
        $image = \imagecreatefromstring($resource);

        if (false === $image) {
            throw new \RuntimeException('Unexpected false image');
        }

        return $image;
    }

    /**
     * @param resource $resource
     * @return array<int>
     */
    public static function imageResolution($resource): array
    {
        $imageResolution = \imageresolution($resource);

        if (false === $imageResolution) {
            throw new \RuntimeException('Unexpected false resolution');
        }

        return $imageResolution;
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function imageSize(string $filePath): array
    {
        $imageSize = \getimagesize($filePath);

        if (false === $imageSize) {
            throw new \RuntimeException('Unexpected false image size');
        }

        return $imageSize;
    }
}