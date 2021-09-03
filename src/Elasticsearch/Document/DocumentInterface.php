<?php

namespace EMS\CommonBundle\Elasticsearch\Document;

interface DocumentInterface
{
    public function getId(): string;

    public function getContentType(): string;

    public function getEmsId(): string;

    /**
     * @return array<mixed>
     */
    public function getSource(): array;

    public function getEMSSource(): EMSSourceInterface;
}
