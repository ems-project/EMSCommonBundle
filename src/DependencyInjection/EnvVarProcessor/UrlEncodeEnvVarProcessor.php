<?php

namespace EMS\CommonBundle\DependencyInjection\EnvVarProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class UrlEncodeEnvVarProcessor implements EnvVarProcessorInterface
{
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        $env = $getEnv($name);

        return \urlencode($env);
    }

    public static function getProvidedTypes()
    {
        return [
            'urlencode' => 'string',
        ];
    }
}
