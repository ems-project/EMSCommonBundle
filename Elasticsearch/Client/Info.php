<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Client;

use EMS\CommonBundle\Contracts\Elasticsearch\Cluster\InfoInterface;

final class Info implements InfoInterface
{
    /** @var string */
    private $clusterName;
    /** @var string */
    private $clusterUuid;
    /** @var string */
    private $tagLine;
    /** @var string */
    private $nodeName;
    /** @var string */
    private $versionNumber;
    /** @var string */
    private $versionNumberLucene;
    /** @var \DateTimeImmutable */
    private $versionBuildDate;

    public function __construct(array $info)
    {
        $this->clusterName = $info['cluster_name'];
        $this->clusterUuid = $info['cluster_uuid'];
        $this->tagLine = $info['tagline'];
        $this->nodeName = $info['name'];
        $this->versionNumber = $info['version']['number'];
        $this->versionNumberLucene = $info['version']['lucene_version'];
        $this->versionBuildDate = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u\Z', $info['version']['build_date']);
    }

    public function getClusterName(): string
    {
        return $this->clusterName;
    }

    public function getClusterUuid(): string
    {
        return $this->clusterUuid;
    }

    public function getTagLine(): string
    {
        return $this->tagLine;
    }

    public function getNodeName(): string
    {
        return $this->nodeName;
    }

    public function getVersionNumber(): string
    {
        return $this->versionNumber;
    }

    public function getVersionNumberLucene(): string
    {
        return $this->versionNumberLucene;
    }

    public function getVersionBuildDate(): \DateTimeImmutable
    {
        return $this->versionBuildDate;
    }
}
