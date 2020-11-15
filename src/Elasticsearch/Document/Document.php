<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

use Elastica\Result;

class Document implements DocumentInterface
{
    /** @var string */
    private $id;
    /** @var string */
    private $contentType;
    /** @var array<mixed> */
    private $source;
    /** @var string */
    private $index;
    /** @var array<string, mixed> */
    private $raw;
    /** @var string|null*/
    private $highlight;

    /**
     * @param Result|array<string, mixed> $document
     */
    public function __construct($document)
    {
        if ($document instanceof Result) {
            $document = $document->getHit();
        }
        $this->id = $document['_id'];
        $this->source = $document['_source'] ?? [];
        $this->index = $document['_index'];
        $this->highlight = $document['highlight'] ?? null;
        $contentType = $document['_source'][EMSSource::FIELD_CONTENT_TYPE] ?? null;
        if ($contentType === null) {
            $contentType = $document['_type'] ?? null;
            @trigger_error(sprintf('The field %s is missing in the document %s', EMSSource::FIELD_CONTENT_TYPE, $this->getEmsId()), E_USER_DEPRECATED);
        }
        if ($contentType === null) {
            throw new \RuntimeException(sprintf('Unable to determine the content type for document %s', $this->id));
        }
        $this->contentType = $contentType;
        $this->raw = $document;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getEmsId(): string
    {
        return sprintf('%s:%s', $this->contentType, $this->id);
    }

    /**
     * @return array<mixed>
     */
    public function getSource(): array
    {
        return $this->source;
    }

    public function getEMSSource(): EMSSourceInterface
    {
        return new EMSSource($this->source);
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    /**
     * @deprecated
     * @return array<string, mixed>
     */
    public function getRaw(): array
    {
        @trigger_error("Document::getRaw is deprecated use the others getters", E_USER_DEPRECATED);
        return $this->raw;
    }

    public function getHighlight(): ?string
    {
        return $this->highlight;
    }
}
