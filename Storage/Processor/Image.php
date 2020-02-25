<?php

namespace EMS\CommonBundle\Storage\Processor;

class Image
{
    /** @var Config */
    private $config;
    /** @var null|string */
    private $watermark;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function setWatermark(string $watermark): void
    {
        $this->watermark = $watermark;
    }

    public function generate(string $filename)
    {
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);

        if (!$image = @imagecreatefromstring($contents)) {
            throw new \InvalidArgumentException('could not make image');
        }

        $size = @getimagesizefromstring($contents);
        list ($width, $height) = $this->getWidthHeight($size);

        if (null !== $this->config->getResize()) {
            $image = $this->applyResizeAndBackground($image, $width, $height, $size);
        } else if (null !== $this->config->getBackground()) {
            $image = $this->applyBackground($image, $width, $height);
        }

        if ($this->config->getRadius() > 0) {
            $image = $this->applyCorner($image, $width, $height);
        }

        if (null !== $this->watermark) {
            $image = $this->applyWatermark($image, $width, $height);
        }

        $path = tempnam(sys_get_temp_dir(), 'ems_image');
        if ($this->config->getQuality()  > 0) {
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


        if ($this->config->getResize() !== 'ratio') {
            $width = ($width == '*' ? $originalWidth : $width);
            $height = ($height == '*' ? $originalHeight : $height);
            return [$width, $height];
        }

        $ratio = $originalWidth / $originalHeight;

        if ($width == '*' && $height == '*') {
            //unable to calculate ratio, silently return original size (backward compatibility)
            return [$originalWidth, $originalHeight];
        }

        if ($width == '*' || $height == '*') {
            if ($height == '*') {
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
            \hexdec(\substr($background, 1, 2)),
            \hexdec(\substr($background, 3, 2)),
            \hexdec(\substr($background, 5, 2)),
            \intval(\hexdec(\substr($background, 7, 2) ?? '00') / 2)
        );
        imagefill($temp, 0, 0, $solidColour);
    }

    private function applyResizeAndBackground($image, $width, $height, $size)
    {
        if (function_exists("imagecreatetruecolor") && ($temp = imagecreatetruecolor($width, $height))) {
            $resizeFunction = 'imagecopyresampled';
        } else {
            $temp = imagecreate($width, $height);
            $resizeFunction = 'imagecopyresized';
        }

        $this->fillBackgroundColor($temp);

        $resize = $this->config->getResize();
        $gravity = $this->config->getGravity();

        if ($resize == 'fillArea') {
            if (($size[1] / $height) < ($size[0] / $width)) {
                $cal_width = $size[1] * $width / $height;
                if (stripos($gravity, 'west') !== false) {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $cal_width, $size[1]);
                } else if (stripos($gravity, 'east') !== false) {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, $size[0] - $cal_width, 0, $width, $height, $cal_width, $size[1]);
                } else {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, ($size[0] - $cal_width) / 2, 0, $width, $height, $cal_width, $size[1]);
                }
            } else {
                $cal_height = $size[0] / $width * $height;
                if (stripos($gravity, 'north') !== false) {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, 0, 0, $width, $height, $size[0], $cal_height);
                } else if (stripos($gravity, 'south') !== false) {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, 0, $size[1] - $cal_height, $width, $height, $size[0], $cal_height);
                } else {
                    call_user_func($resizeFunction, $temp, $image, 0, 0, 0, ($size[1] - $cal_height) / 2, $width, $height, $size[0], $cal_height);
                }
            }
        } else if ($resize == 'fill') {
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
        if (function_exists("imagecreatetruecolor") && ($temp = imagecreatetruecolor($width, $height))) {
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
        $clearColor = imagecolorallocate($cornerImage, 0, 0, 0);
        $solidColor = imagecolorallocate($cornerImage, hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2)));

        imagecolortransparent($cornerImage, $clearColor);
        imagefill($cornerImage, 0, 0, $solidColor);
        imagefilledellipse($cornerImage, $radius, $radius, $radius * 2, $radius * 2, $clearColor);

        $radiusGeometry = $this->config->getRadiusGeometry();

        //render the top-left, bottom-left, bottom-right, top-right corners by rotating and copying the mask
        if (in_array("topleft", $radiusGeometry) !== false) {
            imagecopymerge($image, $cornerImage, 0, 0, 0, 0, $radius, $radius, 100);
        }
        $cornerImage = imagerotate($cornerImage, 90, 0);

        if (in_array("bottomleft", $radiusGeometry) !== false) {
            imagecopymerge($image, $cornerImage, 0, $height - $radius, 0, 0, $radius, $radius, 100);
        }
        $cornerImage = imagerotate($cornerImage, 90, 0);

        if (in_array("bottomright", $radiusGeometry) !== false) {
            imagecopymerge($image, $cornerImage, $width - $radius, $height - $radius, 0, 0, $radius, $radius, 100);
        }
        $cornerImage = imagerotate($cornerImage, 90, 0);

        if (in_array("topright", $radiusGeometry) !== false) {
            imagecopymerge($image, $cornerImage, $width - $radius, 0, 0, 0, $radius, $radius, 100);
        }

        $transparentColor = imagecolorallocate($image, hexdec(substr($color, 1, 2)), hexdec(substr($color, 3, 2)), hexdec(substr($color, 5, 2)));
        imagecolortransparent($image, $transparentColor);

        return $image;
    }

    private function applyWatermark($image, $width, $height)
    {
        $stamp = imagecreatefrompng($this->watermark);
        $sx = imagesx($stamp);
        $sy = imagesy($stamp);
        imagecopy($image, $stamp, ($width - $sx) / 2, ($height - $sy) / 2, 0, 0, $sx, $sy);

        return $image;
    }
}
