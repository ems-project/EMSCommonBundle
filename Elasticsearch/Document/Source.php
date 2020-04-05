<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

use EMS\CommonBundle\Contracts\Elasticsearch\Document\SourceInterface;
use EMS\CommonBundle\Exception\DateTimeCreationException;

final class Source implements SourceInterface
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
        $this->contentType = $source['_contenttype'];
        $this->finalizedBy = $source['_finalized_by'];
        $this->hash = $source['_sha1'];
        $this->source = $source;

        $finalizationDateTime = \DateTimeImmutable::createFromFormat(
            \DATE_ATOM,
            $source['_finalization_datetime']
        );
        if ($finalizationDateTime === false) {
            throw DateTimeCreationException::fromArray($source, '_finalization_datetime');
        }
        $this->finalizationDateTime = $finalizationDateTime;

        $publicationDateTime = \DateTimeImmutable::createFromFormat(
            \DATE_ATOM,
            $source['_published_datetime']
        );
        if ($publicationDateTime === false) {
            throw DateTimeCreationException::fromArray($source, '_published_datetime');
        }
        $this->publicationDateTime = $publicationDateTime;
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
