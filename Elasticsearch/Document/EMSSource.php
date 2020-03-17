<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

final class EMSSource implements EMSSourceInterface
{
    /** @var string */
    private $contentType;
    /** @var string */
    private $finalizedBy;
    /** @var \DateTimeImmutable */
    private $finalizationDateTime;
    /** @var string */
    private $hash;
    /** @var \DateTimeImmutable */
    private $publicationDateTime;
    /** @var array */
    private $source;

    public const FIELD_CONTENT_TYPE = '_contenttype';
    public const FIELD_FINALIZED_BY = '_finalized_by';
    public const FIELD_FINALIZATION_DATETIME = '_finalization_datetime';
    public const FIELD_HASH = '_sha1';
    public const FIELD_PUBLICATION_DATETIME = '_published_datetime';


    public function __construct(array $source)
    {
        $this->contentType = $source[self::FIELD_CONTENT_TYPE] ?? null;
        $this->finalizedBy = $source[self::FIELD_FINALIZED_BY] ?? null;
        $this->hash = $source[self::FIELD_HASH] ?? null;
        $this->source = $source;

        $this->finalizationDateTime = \DateTimeImmutable::createFromFormat(
            \DATE_ATOM,
            $source[self::FIELD_FINALIZATION_DATETIME]
        );
        $this->publicationDateTime = \DateTimeImmutable::createFromFormat(
            \DATE_ATOM,
            $source[self::FIELD_PUBLICATION_DATETIME]
        );
    }

    public function get(string $field, $default = null)
    {
        return $this->source[$field] ?? $default;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getFinalizedBy(): string
    {
        return $this->finalizedBy;
    }

    public function getFinalizationDateTime(): \DateTimeImmutable
    {
        return $this->finalizationDateTime;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getPublicationDateTime(): \DateTimeImmutable
    {
        return $this->publicationDateTime;
    }

    public function toArray(): array
    {
        return $this->source;
    }
}