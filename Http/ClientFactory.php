<?php

namespace EMS\CommonBundle\Http;

use GuzzleHttp\Client;

class ClientFactory
{
    /**
     * @param string $baseUrl
     * @param array  $headers
     * @param int    $timeout
     *
     * @return Client
     */
    public static function create(string $baseUrl, array $headers = [], $timeout = 30): Client
    {
        return new Client([
            'base_uri' => $baseUrl,
            'headers'  => $headers,
            'timeout'  => $timeout
        ]);
    }
}