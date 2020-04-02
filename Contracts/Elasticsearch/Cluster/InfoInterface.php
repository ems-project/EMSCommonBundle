<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\Elasticsearch\Cluster;

interface InfoInterface
{
    public function getClusterName(): string;
    public function getClusterUuid(): string;
    public function getTagLine(): string;
    public function getNodeName(): string;
    public function getVersionNumber(): string;
    public function getVersionNumberLucene(): string;
    public function getVersionBuildDate(): \DateTimeImmutable;
}
