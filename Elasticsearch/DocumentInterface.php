<?php

namespace EMS\CommonBundle\Elasticsearch;

interface DocumentInterface
{
    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getEmsId(): string;

    /**
     * @return array
     */
    public function getSource(): array;
}
