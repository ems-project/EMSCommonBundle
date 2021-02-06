<?php

declare(strict_types=1);

namespace EMS\CommonBundle\Contracts\CoreApi;

use Symfony\Contracts\HttpClient\ResponseInterface;

final class CoreApiException extends \RuntimeException
{
    public static function notAuthenticated(ResponseInterface $response): self
    {
        $info = $response->getInfo();
        $message = \sprintf('%s Unauthorized for [%s] %s', $info['http_code'], $info['http_method'], $info['url']);

        return new self($message);
    }

    public static function notSuccessful(ResponseInterface $response): self
    {
        $info = $response->getInfo();
        $message = \sprintf('[%s] %s was not successful! (Check logs!)', $info['http_method'], $info['url']);

        return new self($message);
    }
}
