<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Document;

interface EMSSourceInterface
{
    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $field, $default = null);

    public function getContentType(): string;

    public function getFinalizedBy(): string;

    public function getFinalizationDateTime(): \DateTimeImmutable;

    public function getHash(): ?string;

    public function getPublicationDateTime(): \DateTimeImmutable;

    /**
     * @return array<mixed>
     */
    public function toArray(): array;
}
