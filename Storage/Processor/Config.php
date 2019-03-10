<?php

namespace EMS\CommonBundle\Storage\Processor;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use function strpos;

final class Config
{
    /** @var string */
    private $processor;
    /** @var string */
    private $assetHash;
    /** @var array */
    private $options;
    /** @var string */
    private $configHash;

    /**
     * Config constructor.
     * @param string $processor, this parameter should be removed in a near futur
     * @param string $assetHash
     * @param string $configHash
     * @param array $options
     */
    public function __construct(string $processor, string $assetHash, string $configHash, array $options = [])
    {
        $this->processor = $processor;
        $this->assetHash = $assetHash;
        $this->options = $this->resolve($options);
        $this->configHash = $configHash;

        unset($options['_published_datetime']); //the published date can't invalidate the cache as it'sbased on the config hash now.

    }

    public function getProcessor(): string
    {
        return $this->processor;
    }

    public function getAssetHash(): string
    {
        return $this->assetHash;
    }

    public function getConfigHash(): string
    {
        return $this->configHash;
    }

    /**
     * Asset_config_type is optional, so _published_datetime can be null
     */
    public function isValid(\DateTime $lastCacheDate = null): bool
    {
        $publishedDateTime = $this->getLastUpdateDate();

        if ($publishedDateTime && $publishedDateTime < $lastCacheDate) {
            return true;
        }

        return null === $publishedDateTime && null !== $lastCacheDate;
    }

    public function getLastUpdateDate(): ?\DateTime
    {
        return $this->options['_published_datetime'];
    }

    public function getCacheKey(): string
    {
        return $this->assetHash . '_' . $this->configHash;
    }

    public function getConfigType(): ?string
    {
        return $this->options['_config_type'];
    }

    public function getQuality(): ?int
    {
        return $this->options['_quality'];
    }

    public function getBackground(): string
    {
        return $this->options['_background'];
    }

    public function getResize(): ?string
    {
        return $this->options['_resize'];
    }

    public function getWidth(): string
    {
        return $this->options['_width'];
    }

    public function getHeight(): string
    {
        return $this->options['_height'];
    }

    public function getGravity(): string
    {
        return $this->options['_gravity'];
    }

    public function getRadius(): ?string
    {
        return $this->options['_radius'];
    }

    public function getRadiusGeometry(): array
    {
        return $this->options['_radius_geometry'];
    }

    public function getBorderColor(): ?string
    {
        return $this->options['_border_color'];
    }

    public function getDisposition(): string
    {
        return $this->options['_disposition'];
    }

    public function getWatermark(): ?string
    {
        return isset($this->options['_watermark']['sha1']) ? $this->options['_watermark']['sha1'] : null;
    }

    public function getMimeType(): string
    {
        if($this->getConfigType() == 'image') {
            if ($this->isSvg()) {
                return $this->options['_mime_type'];
            }

            return $this->getQuality() ? 'image/jpeg' : 'image/png';
        }

        return $this->options['_mime_type'];
    }

    public function cacheableResult()
    {
        //returns the asset itself (it already in the cahce
        if(!$this->getStorageContext()) {
            return false;
        }
        if( $this->getConfigType() == 'image' && strpos($this->options['_mime_type'], 'image/') === 0 && !$this->isSvg() ) {
            return true;
        }
        return false;
    }



    public function getFilenameExtension(): string
    {
        if($this->getStorageContext()) {
            switch ($this->getMimeType()){
                case 'image/jpeg': return '.jpeg';
                case 'image/png': return '.png';
            }
        }
        return '';
    }

    public function getStorageContext(): ?string
    {
        if($this->getConfigType() == 'image') {
            if ($this->isSvg()) {
                return null;
            }

            return $this->getConfigHash();
        }

        return null;
    }

    public function isSvg(): bool
    {
        return $this->options['_mime_type'] ? preg_match('/image\/svg.*/', $this->options['_mime_type']) : false;
    }

    private function resolve(array $options): array
    {
        $defaults = self::getDefaults();

        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults($defaults)
            ->setAllowedValues('_config_type', [null, 'image'])
            ->setAllowedValues('_disposition', [ResponseHeaderBag::DISPOSITION_INLINE, ResponseHeaderBag::DISPOSITION_ATTACHMENT])
            ->setAllowedValues('_radius_geometry', function ($values) use ($defaults) {
                if (!is_array($values)){
                    return false;
                }

                foreach ($values as $value) {
                    if (!in_array($value, $defaults['_radius_geometry'])){
                        throw new UndefinedOptionsException(sprintf('_radius_geometry %s is invalid (%s)', $value, implode(',', $defaults['_radius_geometry'])));
                    }
                }

                return true;
            })
            ->setNormalizer('_published_datetime', function (Options $options, $value) {
                return null !== $value ? new \DateTime($value) : null;
            })
        ;

        return $resolver->resolve($options);
    }

    public static function getDefaults(): array
    {
        return [
            '_config_type' => null,
            '_quality' => 70,
            '_background' => '#FFFFFF',
            '_resize' => 'fill',
            '_width' => 300,
            '_height' => 200,
            '_gravity' => 'center',
            '_radius' => null,
            '_radius_geometry' => ['topleft', 'topright', 'bottomright', 'bottomleft'],
            '_border_color' => null,
            '_watermark' => null,
            '_published_datetime' => '2018-02-05T16:08:56+01:00',
            '_mime_type' => 'application/octet-stream',
            '_disposition' => ResponseHeaderBag::DISPOSITION_INLINE,
        ];
    }
}