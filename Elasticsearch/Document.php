<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch;

/**
 * @deprecated use EMS\CommonBundle\Elasticsearch\Document\Document
 */
final class Document implements DocumentInterface
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

    public function __construct(array $document)
    {
        $this->id = $document['_id'];
        $this->type = $document['_type'];
        $this->source = $document['_source'] ?? [];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEmsId(): string
    {
        return "$this->type:$this->id";
    }

    public function getSource(): array
    {
        return $this->source;
    }
}
