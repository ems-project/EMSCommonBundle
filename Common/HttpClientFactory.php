<?php

namespace EMS\CommonBundle\Common;

use GuzzleHttp\Client;

class HttpClientFactory
{
    /**
     * @param string $baseUrl
     * @param array  $headers
     * @param int    $timeout
     * @param bool   $allowRedirects
     *
     * @return Client
     */
    public static function create(string $baseUrl, array $headers = [], $timeout = 30, bool $allowRedirects = false): Client
    {
        return new Client([
            'base_uri' => $baseUrl,
            'headers'  => $headers,
            'timeout'  => $timeout,
            'allow_redirects' => $allowRedirects,
        ]);
    }
}
