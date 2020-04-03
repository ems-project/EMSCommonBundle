<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch\Document;

interface SourceInterface
{
    public function get(string $field, $default = null);
    public function getContentType(): string;
    public function getFinalizedBy(): string;
    public function getFinalizationDateTime(): \DateTimeImmutable;
    public function getHash(): ?string;
    public function getPublicationDateTime(): \DateTimeImmutable;

    /**
     * @deprecated Please use the other public functions, this toArray() function is for legacy code.
     */
    public function toArray(): array;
}
