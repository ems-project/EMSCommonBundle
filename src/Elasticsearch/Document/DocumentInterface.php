<?php

namespace EMS\CommonBundle\Elasticsearch\Document;

interface DocumentInterface
{
    public function getId(): string;
    public function getContentType(): string;
    public function getEmsId(): string;
    public function getSource(): array;
    public function getEMSSource(): EMSSourceInterface;
}
