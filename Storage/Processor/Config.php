<?php

namespace EMS\CommonBundle\Storage\Processor;

use EMS\CommonBundle\Helper\EmsFields;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
    /** @var ?string */
    private $filename;
    /** @var StorageManager */
    private $storageManager;

    /**
     * Config constructor.
     * @param StorageManager $storageManager
     * @param string $processor, this parameter should be removed in a near futur
     * @param string $assetHash
     * @param string $configHash
     * @param array $options
     */
    public function __construct(StorageManager $storageManager, string $processor, string $assetHash, string $configHash, array $options = [])
    {
        $this->storageManager = $storageManager;
        $this->processor = $processor;
        $this->assetHash = $assetHash;
        $this->options = $this->resolve($options);
        $this->configHash = $configHash;
        $this->filename = null;

        if ($this->getFileNames() !== null) {
            foreach ($this->getFileNames() as $filename) {
                if (is_file($filename)) {
                    $this->filename = $filename;
                    $this->assetHash = $this->storageManager->computeFileHash($filename);
                    break;
                }
            }
        }

        unset($options[EmsFields::CONTENT_PUBLISHED_DATETIME_FIELD]); //the published date can't invalidate the cache as it'sbased on the config hash now.
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

    public function getFilename(): ?string
    {
        return $this->filename;
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
        return $this->options[EmsFields::CONTENT_PUBLISHED_DATETIME_FIELD];
    }

    public function getCacheKey(): string
    {
        return $this->assetHash . '_' . $this->configHash;
    }

    public function getConfigType(): ?string
    {
        return $this->options[EmsFields::ASSET_CONFIG_TYPE];
    }

    public function getQuality(): ?int
    {
        return $this->options[EmsFields::ASSET_CONFIG_QUALITY];
    }

    public function getFileNames(): ?array
    {
        return $this->options[EmsFields::ASSET_CONFIG_FILE_NAMES];
    }

    public function getBackground(): string
    {
        return $this->options[EmsFields::ASSET_CONFIG_BACKGROUND];
    }

    public function getResize(): ?string
    {
        return $this->options[EmsFields::ASSET_CONFIG_RESIZE];
    }

    public function getWidth(): string
    {
        return $this->options[EmsFields::ASSET_CONFIG_WIDTH];
    }

    public function getHeight(): string
    {
        return $this->options[EmsFields::ASSET_CONFIG_HEIGHT];
    }

    public function getGravity(): string
    {
        return $this->options[EmsFields::ASSET_CONFIG_GRAVITY];
    }

    public function getRadius(): ?string
    {
        return $this->options[EmsFields::ASSET_CONFIG_RADIUS];
    }

    public function getRadiusGeometry(): array
    {
        return $this->options[EmsFields::ASSET_CONFIG_RADIUS_GEOMETRY];
    }

    public function getBorderColor(): ?string
    {
        return $this->options[EmsFields::ASSET_CONFIG_BORDER_COLOR];
    }

    public function getDisposition(): string
    {
        return $this->options[EmsFields::ASSET_CONFIG_DISPOSITION];
    }

    public function getWatermark(): ?string
    {
        return isset($this->options[EmsFields::ASSET_CONFIG_WATERMARK_HASH]) ? $this->options[EmsFields::ASSET_CONFIG_WATERMARK_HASH] : null;
    }

    public function getMimeType(): string
    {
        return $this->options[EmsFields::ASSET_CONFIG_MIME_TYPE];
    }

    public function cacheableResult()
    {
        //returns the asset itself (it already in the cache
        if (!$this->getStorageContext()) {
            return false;
        }
        if ($this->getConfigType() == EmsFields::ASSET_CONFIG_TYPE_IMAGE && strpos($this->options[EmsFields::ASSET_CONFIG_MIME_TYPE], 'image/') === 0 && !$this->isSvg()) {
            return true;
        }
        return false;
    }

    public function getStorageContext(): ?string
    {
        if ($this->getConfigType() == EmsFields::ASSET_CONFIG_TYPE_IMAGE) {
            if ($this->isSvg()) {
                return null;
            }

            return $this->getConfigHash();
        }

        return null;
    }

    public function isSvg(): bool
    {
        return $this->options[EmsFields::ASSET_CONFIG_MIME_TYPE] ? preg_match('/image\/svg.*/', $this->options[EmsFields::ASSET_CONFIG_MIME_TYPE]) : false;
    }

    private function resolve(array $options): array
    {
        $defaults = self::getDefaults();

        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults($defaults)
            ->setAllowedValues(EmsFields::ASSET_CONFIG_TYPE, [null, EmsFields::ASSET_CONFIG_TYPE_IMAGE])
            ->setAllowedValues(EmsFields::ASSET_CONFIG_DISPOSITION, [ResponseHeaderBag::DISPOSITION_INLINE, ResponseHeaderBag::DISPOSITION_ATTACHMENT])
            ->setAllowedValues(EmsFields::ASSET_CONFIG_RADIUS_GEOMETRY, function ($values) use ($defaults) {
                if (!is_array($values)) {
                    return false;
                }

                foreach ($values as $value) {
                    if (!in_array($value, $defaults[EmsFields::ASSET_CONFIG_RADIUS_GEOMETRY])) {
                        throw new UndefinedOptionsException(sprintf('_radius_geometry %s is invalid (%s)', $value, implode(',', $defaults[EmsFields::ASSET_CONFIG_RADIUS_GEOMETRY])));
                    }
                }

                return true;
            })
            ->setNormalizer(EmsFields::CONTENT_PUBLISHED_DATETIME_FIELD, function (Options $options, $value) {
                return null !== $value ? new \DateTime($value) : null;
            })
        ;

        return $resolver->resolve($options);
    }

    public static function getDefaults(): array
    {
        return [
            EmsFields::ASSET_CONFIG_TYPE => null,
            EmsFields::ASSET_CONFIG_FILE_NAMES => null,
            EmsFields::ASSET_CONFIG_QUALITY => 70,
            EmsFields::ASSET_CONFIG_BACKGROUND => '#FFFFFF',
            EmsFields::ASSET_CONFIG_RESIZE => 'fill',
            EmsFields::ASSET_CONFIG_WIDTH => 300,
            EmsFields::ASSET_CONFIG_HEIGHT => 200,
            EmsFields::ASSET_CONFIG_GRAVITY => 'center',
            EmsFields::ASSET_CONFIG_RADIUS => null,
            EmsFields::ASSET_CONFIG_RADIUS_GEOMETRY => ['topleft', 'topright', 'bottomright', 'bottomleft'],
            EmsFields::ASSET_CONFIG_BORDER_COLOR => null,
            EmsFields::ASSET_CONFIG_WATERMARK_HASH => null,
            EmsFields::CONTENT_PUBLISHED_DATETIME_FIELD => '2018-02-05T16:08:56+01:00',
            EmsFields::ASSET_CONFIG_MIME_TYPE => 'application/octet-stream',
            EmsFields::ASSET_CONFIG_DISPOSITION => ResponseHeaderBag::DISPOSITION_INLINE,
            EmsFields::ASSET_CONFIG_GET_FILE_PATH => false,
        ];
    }
}
