<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

class Document implements DocumentInterface
{
    /** @var string */
    private $id;
    /** @var string */
    private $contentType;
    /** @var array */
    private $source;

    public function __construct(array $document)
    {
        $this->id = $document['_id'];
        $this->contentType = $document['source']['_contenttype'] ?? $document['_type'];
        $this->source = $document['_source'] ?? [];
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

    public function getSource(): array
    {
        return $this->source;
    }

    public function getEMSSource(): EMSSourceInterface
    {
        return new EMSSource($this->source);
    }
}
