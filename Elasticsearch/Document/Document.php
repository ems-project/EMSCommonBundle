<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

use EMS\CommonBundle\Contracts\Elasticsearch\Document\DocumentInterface;
use EMS\CommonBundle\Contracts\Elasticsearch\Document\SourceInterface;

class Document implements DocumentInterface
{
    /** @var string */
    private $contentType;
    /** @var string */
    private $id;
    /** @var array */
    private $source;

    public function __construct(array $document)
    {
        $this->id = $document['_id'];
        $this->contentType = $document['source']['_contenttype'] ?? $document['_type'];
        $this->source = $document['_source'] ?? [];
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getEmsId(): string
    {
        return sprintf('%s:%s', $this->contentType, $this->id);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSource(): SourceInterface
    {
        return new Source($this->source);
    }
}
