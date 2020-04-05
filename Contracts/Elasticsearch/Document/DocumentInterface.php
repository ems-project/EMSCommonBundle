<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch\Document;

interface DocumentInterface
{
    public function getContentType(): string;
    public function getEmsId(): string;
    public function getId(): string;
    public function getSource(): SourceInterface;
}
