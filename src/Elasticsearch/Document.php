<?php

namespace EMS\CommonBundle\Elasticsearch;

/**
 * @deprecated use EMS\CommonBundle\Elasticsearch\Document\Document
 */
class Document implements DocumentInterface
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $source;

    /**
     * @param array $document
     */
    public function __construct(array $document)
    {
        $this->id = $document['_id'];
        $this->type = $document['_type'];
        $this->source = $document['_source'] ?? [];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getEmsId(): string
    {
        return "$this->type:$this->id";
    }

    /**
     * @return array
     */
    public function getSource(): array
    {
        return $this->source;
    }
}
