<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Common;

class Document
{
    /** @var string */
    private $contentType;
    /** @var string */
    private $ouuid;
    /** @var array */
    private $source;

    public function __construct(string $contentType, string $ouuid, array $source)
    {
        $this->contentType = $contentType;
        $this->ouuid = $ouuid;
        $this->source = $source;
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
