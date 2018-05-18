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
     * @return array
     */
    public function getBody(): array;
}