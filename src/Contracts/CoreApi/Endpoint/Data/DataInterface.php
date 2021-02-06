<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi\Endpoint\Data;

interface DataInterface
{
    /**
     * @param array<string, mixed> $rawData
     */
    public function create(array $rawData, ?string $ouuid = null): DraftInterface;

    public function delete(string $ouuid): bool;

    public function discard(int $revisionId): bool;

    public function finalize(int $revisionId): string;

    public function get(string $ouuid): RevisionInterface;

    /**
     * @param array<string, mixed> $rawData
     */
    public function replace(string $ouuid, array $rawData): DraftInterface;

    /**
     * @param array<string, mixed> $rawData
     */
    public function update(string $ouuid, array $rawData): DraftInterface;
}
