<?php


namespace EMS\CommonBundle\Entity;


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

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     * @return Document
     */
    public function setContentType(string $contentType): Document
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * @return string
     */
    public function getOuuid(): string
    {
        return $this->ouuid;
    }

    /**
     * @param string $ouuid
     * @return Document
     */
    public function setOuuid(string $ouuid): Document
    {
        $this->ouuid = $ouuid;
        return $this;
    }

    /**
     * @return array
     */
    public function getSource(): array
    {
        return $this->source;
    }

    /**
     * @param array $source
     * @return Document
     */
    public function setSource(array $source): Document
    {
        $this->source = $source;
        return $this;
    }


}