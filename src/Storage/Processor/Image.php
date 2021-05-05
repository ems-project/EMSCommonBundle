<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Storage\Processor;

class Image
{
    /** @var Config */
    private $config;
    /** @var string|null */
    private $watermark;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function setWatermark(string $watermark): void
    {
        $this->watermark = $watermark;
    }

    public function generate(string $filename, string $cacheFilename = null)
    {
        $length = \filesize($filename);
        if (false === $length) {
            throw new \RuntimeException('Could not read file');
        }

        $handle = \fopen($filename, 'r');
        if (false === $handle) {
            throw new \RuntimeException('Could not open file');
        }
        $contents = \fread($handle, $length);
        \fclose($handle);

        if (false === $contents) {
            throw new \RuntimeException('Could not read file');
        }
        if (!$image = @\imagecreatefromstring($contents)) {
            throw new \InvalidArgumentException('could not make image');
        }

        $image = $this->autorotate($filename, $image);
        $this->applyFlips($image, $this->config->getFlipHorizontal(), $this->config->getFlipVertical());
        $image = $this->rotate($image, $this->config->getRotate());
        $rotatedWidth = \imagesx($image);
        $rotatedHeight = \imagesy($image);

        list($width, $height) = $this->getWidthHeight($rotatedWidth, $rotatedHeight);

        if (null !== $this->config->getResize()) {
            $image = $this->applyResizeAndBackground($image, $width, $height, $rotatedWidth, $rotatedHeight);
        } elseif (null !== $this->config->getBackground()) {
            $image = $this->applyBackground($image, $width, $height);
        }
        if (false === $image) {
            throw new \RuntimeException('Unexpected false image');
        }

        if ($this->config->getRadius() > 0) {
            $image = $this->applyCorner($image, $width, $height);
        }

        $image = $this->applyWatermark($image, $width, $height);

        if (null !== $cacheFilename) {
            if (!\file_exists(\dirname($cacheFilename))) {
                \mkdir(\dirname($cacheFilename), 0777, true);
            }
            $path = $cacheFilename;
        } else {
            $path = \tempnam(\sys_get_temp_dir(), 'ems_image');
            if (false === $path) {
                throw new \RuntimeException('Could not create file with unique name.');
            }
        }
        if ($this->config->getQuality() > 0) {
            \imagejpeg($image, $path, $this->config->getQuality());
        } else {
            \imagepng($image, $path);
        }
        \imagedestroy($image);

        return $path;
    }

    /**
     * @return array<int>
     */
    private function getWidthHeight(int $originalWidth, int $originalHeight): array
    {
        $width = $this->config->getWidth();
        $height = $this->config->getHeight();

        if ('ratio' !== $this->config->getResize()) {
            $width = ('*' == $width ? $originalWidth : $width);
            $height = ('*' == $height ? $originalHeight : $height);

            return [\intval($width), \intval($height)];
        }

        $ratio = $originalWidth / $originalHeight;

        if ('*' == $width && '*' == $height) {
            //unable to calculate ratio, silently return original size (backward compatibility)
            return [\intval($originalWidth), \intval($originalHeight)];
        }

        if ('*' == $width || '*' == $height) {
            if ('*' == $height) {
                // recalculate height
                $height = \ceil((float) $width / $ratio);
            } else {
                // recalculate width
                $width = \ceil($ratio * (float) $height);
            }
        } else {
            if (($originalHeight / $height) > ($originalWidth / $width)) {
                $width = \ceil($ratio * (float) $height);
            } else {
                $height = \ceil((float) $width / $ratio);
            }
        }

        return [\intval($width), \intval($height)];
    }

    private function fillBackgroundColor($temp)
    {
        $solidColour = $this->getBackgroundColor($temp);
        \imagesavealpha($temp, true);
        \imagefill($temp, 0, 0, $solidColour);
    }

    private function applyResizeAndBackground($image, int $width, int $height, int $originalWidth, int $originalHeight)
    {
        if (\function_exists('imagecreatetruecolor') && ($temp = \imagecreatetruecolor($width, $height))) {
            $resizeFunction = 'imagecopyresampled';
        } else {
            $temp = \imagecreate($width, $height);
            $resizeFunction = 'imagecopyresized';
        }

        $this->fillBackgroundColor($temp);

        $resize = $this->config->getResize();
        $gravity = $this->config->getGravity();

        if ('fillArea' == $resize) {
            if (($originalHeight / $height) < ($originalWidth / $width)) {
                $cal_width = \intval($originalHeight * $width / $height);
                if (false !== \stripos($gravity, 'west')) {
                    \call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $cal_width, $originalHeight);
                } elseif (false !== \stripos($gravity, 'east')) {
                    \call_user_func($resizeFunction, $temp, $image, 0, 0, $originalWidth - $cal_width, 0, $width, $height, $cal_width, $originalHeight);
                } else {
                    \call_user_func($resizeFunction, $temp, $image, 0, 0, \intval(($originalWidth - $cal_width) / 2), 0, $width, $height, $cal_width, $originalHeight);
                }
            } else {
                $cal_height = \intval($originalWidth / $width * $height);
                if (false !== \stripos($gravity, 'north')) {
                    \call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $cal_height);
                } elseif (false !== \stripos($gravity, 'south')) {
                    \call_user_func($resizeFunction, $temp, $image, 0, 0, 0, $originalHeight - $cal_height, $width, $height, $originalWidth, $cal_height);
                } else {
                    \call_user_func($resizeFunction, $temp, $image, 0, 0, 0, \intval(($originalHeight - $cal_height) / 2), $width, $height, $originalWidth, $cal_height);
                }
            }
        } elseif ('fill' == $resize) {
            if (($originalHeight / $height) < ($originalWidth / $width)) {
                $thumb_height = \intval($width * $originalHeight / $originalWidth);
                \call_user_func($resizeFunction, $temp, $image, 0, \intval(($height - $thumb_height) / 2), 0, 0, $width, $thumb_height, $originalWidth, $originalHeight);
            } else {
                $thumb_width = \intval(($originalWidth * $height) / $originalHeight);
                \call_user_func($resizeFunction, $temp, $image, \intval(($width - $thumb_width) / 2), 0, 0, 0, $thumb_width, $height, $originalWidth, $originalHeight);
            }
        } else {
            \call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
        }

        return $temp;
    }

    private function applyBackground($image, $width, $height)
    {
        if (\function_exists('imagecreatetruecolor') && ($temp = \imagecreatetruecolor($width, $height))) {
            $resizeFunction = 'imagecopyresampled';
        } else {
            $temp = \imagecreate($width, $height);
            $resizeFunction = 'imagecopyresized';
        }

        $this->fillBackgroundColor($temp);

        \call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $width, $height);

        return $temp;
    }

    private function applyCorner($image, $width, $height)
    {
        $radius = $this->config->getRadius();
        $color = $this->config->getBorderColor() ?? $this->config->getBackground();

        $cornerImage = \imagecreatetruecolor($radius, $radius);
        if (false === $cornerImage) {
            throw new \RuntimeException('Could not create cornerImage');
        }
        $clearColor = \imagecolorallocate($cornerImage, 0, 0, 0);
        if (false === $clearColor) {
            throw new \RuntimeException('Unexpected false imagecolorallocate');
        }
        $solidColor = \imagecolorallocate($cornerImage, (int) \hexdec(\substr($color, 1, 2)), (int) \hexdec(\substr($color, 3, 2)), (int) \hexdec(\substr($color, 5, 2)));
        if (false === $solidColor) {
            throw new \RuntimeException('Unexpected false imagecolorallocate');
        }

        \imagecolortransparent($cornerImage, $clearColor);
        \imagefill($cornerImage, 0, 0, $solidColor);
        \imagefilledellipse($cornerImage, $radius, $radius, $radius * 2, $radius * 2, $clearColor);

        $radiusGeometry = $this->config->getRadiusGeometry();

        //render the top-left, bottom-left, bottom-right, top-right corners by rotating and copying the mask
        if (false !== \in_array('topleft', $radiusGeometry)) {
            \imagecopymerge($image, $cornerImage, 0, 0, 0, 0, $radius, $radius, 100);
        }
        $cornerImage = \imagerotate($cornerImage, 90, 0);
        if (false === $image || false === $cornerImage) {
            throw new \RuntimeException('Unexpected false image');
        }

        if (false !== \in_array('bottomleft', $radiusGeometry)) {
            \imagecopymerge($image, $cornerImage, 0, $height - $radius, 0, 0, $radius, $radius, 100);
        }
        $cornerImage = \imagerotate($cornerImage, 90, 0);
        if (false === $cornerImage) {
            throw new \RuntimeException('Unexpected false image');
        }

        if (false !== \in_array('bottomright', $radiusGeometry)) {
            \imagecopymerge($image, $cornerImage, $width - $radius, $height - $radius, 0, 0, $radius, $radius, 100);
        }
        $cornerImage = \imagerotate($cornerImage, 90, 0);
        if (false === $cornerImage) {
            throw new \RuntimeException('Unexpected false image');
        }

        if (false !== \in_array('topright', $radiusGeometry)) {
            \imagecopymerge($image, $cornerImage, $width - $radius, 0, 0, 0, $radius, $radius, 100);
        }

        $transparentColor = \imagecolorallocate($image, (int) \hexdec(\substr($color, 1, 2)), (int) \hexdec(\substr($color, 3, 2)), (int) \hexdec(\substr($color, 5, 2)));
        if (false === $transparentColor) {
            throw new \RuntimeException('Unexpected false imagecolorallocate');
        }
        \imagecolortransparent($image, $transparentColor);

        return $image;
    }

    private function applyWatermark($image, $width, $height)
    {
        if (null === $this->watermark) {
            return $image;
        }
        $stamp = \imagecreatefrompng($this->watermark);
        if (false === $stamp) {
            throw new \RuntimeException('Could not convert watermark to image');
        }
        $sx = \imagesx($stamp);
        $sy = \imagesy($stamp);
        \imagecopy($image, $stamp, (int) ($width - $sx) / 2, (int) ($height - $sy) / 2, 0, 0, $sx, $sy);

        return $image;
    }

    /**
     * @param resource $image
     */
    private function applyFlips($image, bool $flipHorizontal, bool $flipVertical): void
    {
        if ($flipHorizontal && $flipVertical) {
            \imageflip($image, IMG_FLIP_BOTH);
        } elseif ($flipHorizontal) {
            \imageflip($image, IMG_FLIP_HORIZONTAL);
        } elseif ($flipVertical) {
            \imageflip($image, IMG_FLIP_VERTICAL);
        }
    }

    /**
     * @param resource $image
     *
     * @return resource
     */
    private function rotate($image, float $angle)
    {
        if (0 == $angle) {
            return $image;
        }

        $rotated = \imagerotate($image, $angle, $this->getBackgroundColor($image));
        if (false === $rotated) {
            throw new \RuntimeException('Could not rotate the image');
        }
        \imagedestroy($image);

        return $rotated;
    }

    /**
     * @param resource $temp
     */
    private function getBackgroundColor($temp): int
    {
        $background = $this->config->getBackground();
        $solidColour = \imagecolorallocatealpha(
            $temp,
            (int) \hexdec(\substr($background, 1, 2)),
            (int) \hexdec(\substr($background, 3, 2)),
            (int) \hexdec(\substr($background, 5, 2)),
            \intval(\hexdec(\substr($background, 7, 2) ?? '00') / 2)
        );
        if (false === $solidColour) {
            throw new \RuntimeException('Unexpected false imagecolorallocatealpha');
        }

        return $solidColour;
    }

    /**
     * The 8 EXIF orientation values are numbered 1 to 8.
     * 1 = 0 degrees: the correct orientation, no adjustment is required.
     * 2 = 0 degrees, mirrored: image has been flipped back-to-front.
     * 3 = 180 degrees: image is upside down.
     * 4 = 180 degrees, mirrored: image has been flipped back-to-front and is upside down.
     * 5 = 270 degrees anticlockwise: image has been flipped back-to-front and is on its side.
     * 6 = 270 degrees anticlockwise, mirrored: image is on its side.
     * 7 = 90 degrees anticlockwise: image has been flipped back-to-front and is on its far side.
     * 8 = 90 degrees anticlockwise, mirrored: image is on its far side.
     * ref: https://sirv.com/help/articles/rotate-photos-to-be-upright/.
     *
     * @param resource $image
     *
     * @return resource
     */
    private function autorotate(string $filename, $image)
    {
        if (!$this->config->getAutoRotate()) {
            return $image;
        }

        try {
            $metadata = \exif_read_data($filename);
            if (false === $metadata) {
                return $image;
            }
            $angle = 0;
            $mirrored = false;
            switch ($metadata['Orientation'] ?? 0) {
                case 2:
                    $mirrored = true;
                    break;
                case 3:
                    $angle = 180;
                    break;
                case 4:
                    $angle = 180;
                    $mirrored = true;
                    break;
                case 5:
                    $angle = 270;
                    break;
                case 6:
                    $angle = 270;
                    $mirrored = true;
                    break;
                case 7:
                    $angle = 90;
                    break;
                case 8:
                    $angle = 90;
                    $mirrored = true;
                    break;
            }
            $image = $this->rotate($image, $angle);
            $this->applyFlips($image, $mirrored, false);
        } catch (\Throwable $e) {
            \trigger_error(\sprintf('Not able to autorotate a file due to: %s', $e->getMessage()), E_USER_WARNING);
        }

        return $image;
    }
}
