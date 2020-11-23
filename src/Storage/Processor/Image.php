<?php

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
        $length = filesize($filename);
        if (false === $length) {
            throw new \RuntimeException('Could not read file');
        }

        $handle = fopen($filename, 'r');
        if (false === $handle) {
            throw new \RuntimeException('Could not open file');
        }
        $contents = fread($handle, $length);
        fclose($handle);

        if (false === $contents) {
            throw new \RuntimeException('Could not read file');
        }
        if (!$image = @imagecreatefromstring($contents)) {
            throw new \InvalidArgumentException('could not make image');
        }

        $size = @getimagesizefromstring($contents);
        if (false === $size) {
            throw new \RuntimeException('Could not get size of image');
        }
        list($width, $height) = $this->getWidthHeight($size);

        if (null !== $this->config->getResize()) {
            $image = $this->applyResizeAndBackground($image, $width, $height, $size);
        } elseif (null !== $this->config->getBackground()) {
            $image = $this->applyBackground($image, $width, $height);
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
            $path = tempnam(sys_get_temp_dir(), 'ems_image');
            if (false === $path) {
                throw new \RuntimeException('Could not create file with unique name.');
            }
        }
        if ($this->config->getQuality() > 0) {
            imagejpeg($image, $path, $this->config->getQuality());
        } else {
            imagepng($image, $path);
        }
        imagedestroy($image);

        return $path;
    }

    private function getWidthHeight(array $size): array
    {
        list($originalWidth, $originalHeight) = $size;

        $width = $this->config->getWidth();
        $height = $this->config->getHeight();

        if ('ratio' !== $this->config->getResize()) {
            $width = ('*' == $width ? $originalWidth : $width);
            $height = ('*' == $height ? $originalHeight : $height);

            return [$width, $height];
        }

        $ratio = $originalWidth / $originalHeight;

        if ('*' == $width && '*' == $height) {
            //unable to calculate ratio, silently return original size (backward compatibility)
            return [$originalWidth, $originalHeight];
        }

        if ('*' == $width || '*' == $height) {
            if ('*' == $height) {
                // recalculate height
                $height = ceil((float) $width / $ratio);
            } else {
                // recalculate width
                $width = ceil($ratio * (float) $height);
            }
        } else {
            if (($originalHeight / $height) > ($originalWidth / $width)) {
                $width = ceil($ratio * (float) $height);
            } else {
                $height = ceil((float) $width / $ratio);
            }
        }

        return [$width, $height];
    }

    private function fillBackgroundColor($temp)
    {
        $background = $this->config->getBackground();
        imagesavealpha($temp, true);

        $solidColour = imagecolorallocatealpha(
            $temp,
            (int) \hexdec(\substr($background, 1, 2)),
            (int) \hexdec(\substr($background, 3, 2)),
            (int) \hexdec(\substr($background, 5, 2)),
            \intval(\hexdec(\substr($background, 7, 2) ?? '00') / 2)
        );
        imagefill($temp, 0, 0, $solidColour);
    }

    private function applyResizeAndBackground($image, $width, $height, $size)
    {
        if (function_exists('imagecreatetruecolor') && ($temp = imagecreatetruecolor($width, $height))) {
            $resizeFunction = 'imagecopyresampled';
        } else {
            $temp = imagecreate($width, $height);
            $resizeFunction = 'imagecopyresized';
        }

        $this->fillBackgroundColor($temp);

        $resize = $this->config->getResize();
        $gravity = $this->config->getGravity();

        if ('fillArea' == $resize) {
            if (($size[1] / $height) < ($size[0] / $width)) {
                $cal_width = $size[1] * $width / $height;
                if (false !== stripos($gravity, 'west')) {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $cal_width, $size[1]);
                } elseif (false !== stripos($gravity, 'east')) {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, $size[0] - $cal_width, 0, $width, $height, $cal_width, $size[1]);
                } else {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, ($size[0] - $cal_width) / 2, 0, $width, $height, $cal_width, $size[1]);
                }
            } else {
                $cal_height = $size[0] / $width * $height;
                if (false !== stripos($gravity, 'north')) {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $size[0], $cal_height);
                } elseif (false !== stripos($gravity, 'south')) {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, 0, $size[1] - $cal_height, $width, $height, $size[0], $cal_height);
                } else {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, 0, ($size[1] - $cal_height) / 2, $width, $height, $size[0], $cal_height);
                }
            }
        } elseif ('fill' == $resize) {
            if (($size[1] / $height) < ($size[0] / $width)) {
                $thumb_height = $width * $size[1] / $size[0];
                call_user_func($resizeFunction, $temp, $image, 0, ($height - $thumb_height) / 2, 0, 0, $width, $thumb_height, $size[0], $size[1]);
            } else {
                $thumb_width = ($size[0] * $height) / $size[1];
                call_user_func($resizeFunction, $temp, $image, ($width - $thumb_width) / 2, 0, 0, 0, $thumb_width, $height, $size[0], $size[1]);
            }
        } else {
            call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
        }

        return $temp;
    }

    private function applyBackground($image, $width, $height)
    {
        if (function_exists('imagecreatetruecolor') && ($temp = imagecreatetruecolor($width, $height))) {
            $resizeFunction = 'imagecopyresampled';
        } else {
            $temp = imagecreate($width, $height);
            $resizeFunction = 'imagecopyresized';
        }

        $this->fillBackgroundColor($temp);

        call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $width, $height);

        return $temp;
    }

    private function applyCorner($image, $width, $height)
    {
        $radius = $this->config->getRadius();
        $color = $this->config->getBorderColor() ?? $this->config->getBackground();

        $cornerImage = imagecreatetruecolor($radius, $radius);
        if (false === $cornerImage) {
            throw new \RuntimeException('Could not create cornerImage');
        }
        $clearColor = imagecolorallocate($cornerImage, 0, 0, 0);
        $solidColor = imagecolorallocate($cornerImage, (int) hexdec(substr($color, 1, 2)), (int) hexdec(substr($color, 3, 2)), (int) hexdec(substr($color, 5, 2)));

        imagecolortransparent($cornerImage, $clearColor);
        imagefill($cornerImage, 0, 0, $solidColor);
        imagefilledellipse($cornerImage, $radius, $radius, $radius * 2, $radius * 2, $clearColor);

        $radiusGeometry = $this->config->getRadiusGeometry();

        //render the top-left, bottom-left, bottom-right, top-right corners by rotating and copying the mask
        if (false !== in_array('topleft', $radiusGeometry)) {
            imagecopymerge($image, $cornerImage, 0, 0, 0, 0, $radius, $radius, 100);
        }
        $cornerImage = imagerotate($cornerImage, 90, 0);

        if (false !== in_array('bottomleft', $radiusGeometry)) {
            imagecopymerge($image, $cornerImage, 0, $height - $radius, 0, 0, $radius, $radius, 100);
        }
        $cornerImage = imagerotate($cornerImage, 90, 0);

        if (false !== in_array('bottomright', $radiusGeometry)) {
            imagecopymerge($image, $cornerImage, $width - $radius, $height - $radius, 0, 0, $radius, $radius, 100);
        }
        $cornerImage = imagerotate($cornerImage, 90, 0);

        if (false !== in_array('topright', $radiusGeometry)) {
            imagecopymerge($image, $cornerImage, $width - $radius, 0, 0, 0, $radius, $radius, 100);
        }

        $transparentColor = imagecolorallocate($image, (int) hexdec(substr($color, 1, 2)), (int) hexdec(substr($color, 3, 2)), (int) hexdec(substr($color, 5, 2)));
        imagecolortransparent($image, $transparentColor);

        return $image;
    }

    private function applyWatermark($image, $width, $height)
    {
        if (null === $this->watermark) {
            return $image;
        }
        $stamp = imagecreatefrompng($this->watermark);
        if (false === $stamp) {
            throw new \RuntimeException('Could not convert watermark to image');
        }
        $sx = imagesx($stamp);
        $sy = imagesy($stamp);
        imagecopy($image, $stamp, (int) ($width - $sx) / 2, (int) ($height - $sy) / 2, 0, 0, $sx, $sy);

        return $image;
    }
}
