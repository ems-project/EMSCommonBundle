<?php

namespace EMS\CommonBundle\Common;

class Document
{
    /**@var string */
    private $contentType;
    /**@var string */
    private $ouuid;
    /**@var array */
    private $source;

    public function __construct(string $contentType, string $ouuid, array $source)
    {
        $this->contentType = $contentType;
        $this->ouuid = $ouuid;
        $this->source = $source;
    }

    public function get_type(): string
    {
        @trigger_error(sprintf('The "%s::get_type" function is deprecated. Used "%s::getContentType" instead.', Document::class, Document::class), E_USER_DEPRECATED);
        return $this->getContentType();
    }

    public function getType(): string
    {
        @trigger_error(sprintf('The "%s::getType" function is deprecated. Used "%s::getContentType" instead.', Document::class, Document::class), E_USER_DEPRECATED);
        return $this->getContentType();
    }

    public function get_id(): string
    {
        @trigger_error(sprintf('The "%s::get_id" function is deprecated. Used "%s::getOuuid" instead.', Document::class, Document::class), E_USER_DEPRECATED);
        return $this->getOuuid();
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getOuuid(): string
    {
        return $this->ouuid;
    }

    public function getSource(): array
    {
        return $this->source;
    }
}
