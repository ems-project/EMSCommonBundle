<?php

namespace EMS\CommonBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class RequestRuntime implements RuntimeExtensionInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param array  $array
     * @param string $attribute
     *
     * @return mixed
     */
    public function localeAttribute(array $array, string $attribute)
    {
        $locale = $this->requestStack->getCurrentRequest()->getLocale();

        return isset($array[$attribute.$locale]) ? $array[$attribute.$locale] : '';
    }
}