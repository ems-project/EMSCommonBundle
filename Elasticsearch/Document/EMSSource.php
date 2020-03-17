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

    public function __construct(array $source)
    {
        $this->contentType = $source['_contenttype'] ?? null;
        $this->finalizedBy = $source['_finalized_by'] ?? null;
        $this->hash = $source['_sha1'] ?? null;
        $this->source = $source;

        $this->finalizationDateTime = \DateTimeImmutable::createFromFormat(
            \DATE_ATOM,
            $source['_finalization_datetime']
        );
        $this->publicationDateTime = \DateTimeImmutable::createFromFormat(
            \DATE_ATOM,
            $source['_published_datetime']
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