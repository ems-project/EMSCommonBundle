<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

interface EMSSourceInterface
{
    public function get(string $field, $default = null);
    public function getContentType(): string;
    public function getFinalizedBy(): string;
    public function getFinalizationDateTime(): \DateTimeImmutable;
    public function getHash(): ?string;
    public function getPublicationDateTime(): \DateTimeImmutable;
    public function toArray(): array;
}
