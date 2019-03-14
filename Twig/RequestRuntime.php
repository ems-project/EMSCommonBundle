<?php

namespace EMS\CommonBundle\Twig;

use EMS\CommonBundle\Helper\ArrayTool;
use EMS\CommonBundle\Helper\EmsFields;
use EMS\CommonBundle\Storage\Processor\Config;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class RequestRuntime implements RuntimeExtensionInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var StorageManager */
    private $storageManager;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /**
     * @param RequestStack $requestStack
     * @param StorageManager $storageManager
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(RequestStack $requestStack, StorageManager $storageManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->requestStack = $requestStack;
        $this->storageManager = $storageManager;
        $this->urlGenerator = $urlGenerator;
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

    /**
     * @param array $fileField
     * @param array $assetConfig
     * @param string $route
     * @param string $fileHashField
     * @param string $filenameField
     * @param string $mimeTypeField
     * @param int $referenceType
     * @return string
     */
    public function assetPath(array $fileField, array $assetConfig=[], string $route = 'ems_asset', string $fileHashField=EmsFields::CONTENT_FILE_HASH_FIELD, $filenameField=EmsFields::CONTENT_FILE_NAME_FIELD, $mimeTypeField=EmsFields::CONTENT_MIME_TYPE_FIELD, $referenceType = UrlGeneratorInterface::RELATIVE_PATH) : string
    {
        $config = $assetConfig;
        if(isset($fileField[$mimeTypeField])) {
            $config['_mime_type'] = $fileField[$mimeTypeField];
        }
        elseif (! isset($assetConfig[EmsFields::CONTENT_MIME_TYPE_FIELD]) && isset($fileField[$filenameField])) {
            $config['_mime_type'] = mime_content_type($fileField[$filenameField]);
        }

        $configObject = new Config('', '', '', $config);

        $hashConfig = $this->storageManager->saveContents(ArrayTool::normalizeAndSerializeArray($config), 'assetConfig.json', 'application/json');

        return $this->urlGenerator->generate($route, [
            'hash' => $fileField[$fileHashField],
            'hash_config' => $hashConfig,
            'filename' => (isset($fileField[$filenameField])?$fileField[$filenameField]:'asset').$configObject->getFilenameExtension(),
        ], $referenceType);
    }
}
