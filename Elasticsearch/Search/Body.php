<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Elasticsearch\Search;

final class Body
{
    public static function addContentType(array $body, string $contentType): array
    {
        return self::appendContentTypes($body, [$contentType]);
    }

    public static function addContentTypes(array $body, array $contentTypes): array
    {
        return self::appendContentTypes($body, $contentTypes);
    }

    private static function appendContentTypes(array $body, array $contentTypes): array
    {
        return [
            'query' => [
                'bool' => [
                    'must' => array_filter([
                        [
                            'bool' => [
                                'minimum_should_match' => 1,
                                'should' => [
                                    ['terms' => ['_type' => $contentTypes]],
                                    ['terms' => ['_contenttype' => $contentTypes]],
                                ]
                            ]
                        ],
                        $body['query'] ?? []
                    ])
                ]
            ]
        ];
    }
}
