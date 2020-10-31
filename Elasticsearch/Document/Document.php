<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

class Document implements DocumentInterface
{
    /** @var string */
    private $id;
    /** @var string */
    private $contentType;
    /** @var array<mixed> */
    private $source;

    /**
     * @param array{_id: string, _type?: string, _source: array} $document
     */
    public function __construct(array $document)
    {
        $this->id = $document['_id'];
        $this->contentType = $document['_source'][EMSSource::FIELD_CONTENT_TYPE] ?? null;
        $this->source = $document['_source'] ?? [];
        if ($this->contentType == null) {
            $this->contentType = $document['_type'] ?? null;
            @trigger_error(sprintf('The field %s is missing in the document %s', EMSSource::FIELD_CONTENT_TYPE, $this->getEmsId()), E_USER_DEPRECATED);
        }
        if ($this->contentType == null) {
            throw new \RuntimeException(sprintf('Unable to determine the content type for document %s', $this->id));
        }
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
}
