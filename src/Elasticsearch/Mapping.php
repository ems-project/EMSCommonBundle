<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch;

use EMS\CommonBundle\Elasticsearch\Document\EMSSource;

final class Mapping
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function defaultMapping(): array
    {
        return [
            EMSSource::FIELD_CONTENT_TYPE => $this->getKeywordMapping(),
        ];
    }

    /**
     * @return string[]
     */
    public function getKeywordMapping(): array
    {
        if (\version_compare($this->getVersion(), '5') >= 0) {
            return [
                'type' => 'keyword',
            ];
        }

        return [
            'type' => 'string',
            'index' => 'not_analyzed',
        ];
    }

    public function getVersion(): string
    {
        return $this->client->getVersion();
    }

    /**
     * @return array{type: 'text', index: false}
     */
    public function getNotIndexedStringMapping(): array
    {
        return [
            'type' => 'text',
            'index' => false,
        ];
    }

    /**
     * @return string[]
     */
    public function getDateTimeMapping(): array
    {
        return [
            'type' => 'date',
            'format' => 'date_time_no_millis',
        ];
    }

    /**
     * @return array{type: 'text', index: true}
     */
    public function getIndexedStringMapping(): array
    {
        return [
            'type' => 'text',
            'index' => true,
        ];
    }

    /**
     * @return string[]
     */
    public function getLongMapping(): array
    {
        return [
            'type' => 'long',
        ];
    }

    /**
     * @return string[]
     */
    public function getFloatMapping(): array
    {
        return [
            'type' => 'float',
        ];
    }

    /**
     * @return array<mixed>
     */
    public function getLimitedKeywordMapping(): array
    {
        return [
            'type' => 'keyword',
            'ignore_above' => 256,
        ];
    }

    /**
     * @return array<mixed>
     */
    public function getTextWithSubRawMapping()
    {
        return [
            'type' => 'text',
            'fields' => [
                'raw' => [
                    'type' => 'keyword',
                ],
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function getTextMapping()
    {
        return [
            'type' => 'text',
        ];
    }
}
