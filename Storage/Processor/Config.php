<?php

namespace EMS\CommonBundle\Storage\Processor;

final class Config
{
    /** @var string */
    private $identifier;
    /** @var \DateTime */
    private $lastUpdateDate;

    /** @var string */
    private $configType;
    /** @var null|int */
    private $quality;
    /** @var string */
    private $background;

    /** @var null|string */
    private $resize;
    /** @var string */
    private $width;
    /** @var string */
    private $height;
    /** @var string */
    private $gravity;

    /** @var null|string */
    private $radius;
    /** @var array */
    private $radiusGeometry;
    /** @var null|string */
    private $borderColor;

    /** @var null|array  */
    private $watermark;

    public function __construct(string $identifier, array $doc = [])
    {
        $this->identifier = $identifier;
        $this->configType = $doc['_config_type'] ?? 'image';
        $this->quality = $doc['_quality'] ?? null;
        $this->background = $doc['_background'] ?? '#FFFFFF';

        $this->resize = $doc['_resize'] ?? null;
        $this->width =  $doc['_width'] ?? '*';
        $this->height =  $doc['_height'] ?? '*';
        $this->gravity = $doc['_gravity'] ?? 'center';

        $this->radius = $doc['_radius'] ?? null;
        $this->radiusGeometry = $doc['_radius_geometry'] ?? ['topleft', 'topright', 'bottomright', 'bottomleft'];
        $this->borderColor = $doc['_border_color'] ?? null;

        $this->watermark = $doc['_watermark'] ?? null;
        $this->lastUpdateDate = isset($doc['_published_datetime']) ? new \DateTime($doc['_published_datetime']) : new \DateTime();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLastUpdateDate(): \DateTime
    {
        return $this->lastUpdateDate;
    }

    public function getConfigType(): string
    {
        return $this->configType;
    }

    public function getQuality(): ?int
    {
        return $this->quality;
    }

    public function getBackground(): string
    {
        return $this->background;
    }

    public function getResize(): ?string
    {
        return $this->resize;
    }

    public function getWidth(): string
    {
        return $this->width;
    }

    public function getHeight(): string
    {
        return $this->height;
    }

    public function getGravity(): string
    {
        return $this->gravity;
    }

    public function getRadius(): ?string
    {
        return $this->radius;
    }

    public function getRadiusGeometry(): array
    {
        return $this->radiusGeometry;
    }

    public function getBorderColor(): ?string
    {
        return $this->borderColor;
    }

    public function getWatermark(): ?string
    {
        return $this->watermark['sha1'] ?? null;
    }
}