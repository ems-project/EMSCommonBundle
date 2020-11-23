<?php

namespace EMS\CommonBundle\Elasticsearch;

/**
 * @deprecated use EMS\CommonBundle\Elasticsearch\Document\DocumentInterface
 */
interface DocumentInterface
{
    public function getId(): string;

    public function getType(): string;

    public function getEmsId(): string;

    public function getSource(): array;
}
