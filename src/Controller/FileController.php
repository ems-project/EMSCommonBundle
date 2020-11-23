<?php

namespace EMS\CommonBundle\Controller;

use EMS\CommonBundle\Helper\EmsFields;
use EMS\CommonBundle\Storage\Processor\Processor;
use EMS\CommonBundle\Storage\StorageManager;
use EMS\CommonBundle\Twig\RequestRuntime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends AbstractController
{
    /**
     * @var StorageManager
     */
    private $storageManager;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var RequestRuntime
     */
    private $requestRuntime;

    public function __construct(StorageManager $storageManager, Processor $processor, RequestRuntime $requestRuntime)
    {
        $this->storageManager = $storageManager;
        $this->processor = $processor;
        $this->requestRuntime = $requestRuntime;
    }

    /**
     * @return Response|StreamedResponse
     */
    public function asset(Request $request, string $hash, string $hash_config, string $filename)
    {
        $this->closeSession($request);

        return $this->processor->getResponse($request, $hash, $hash_config, $filename, true);
    }

    /**
     * @return Response
     */
    public function view(Request $request, string $sha1)
    {
        @\trigger_error('FileController::view is deprecated use the ems_asset twig filter to generate the route', E_USER_DEPRECATED);

        $this->closeSession($request);

        return $this->getFile($request, $sha1, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    /**
     * @return Response
     */
    public function download(Request $request, string $sha1)
    {
        @\trigger_error('FileController::download is deprecated use the ems_asset twig filter to generate the route', E_USER_DEPRECATED);

        $this->closeSession($request);

        return $this->getFile($request, $sha1, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    private function getFile(Request $request, string $hash, string $disposition): Response
    {
        @\trigger_error('FileController::download is deprecated use the ems_asset twig filter to generate the route', E_USER_DEPRECATED);

        $name = $request->query->get('name', 'upload.bin');
        $type = $request->query->get('type', 'application/bin');

        return $this->redirect($this->requestRuntime->assetPath([
            EmsFields::CONTENT_FILE_HASH_FIELD => $hash,
            EmsFields::CONTENT_FILE_NAME_FIELD => $name,
            EmsFields::CONTENT_MIME_TYPE_FIELD => $type,
        ], [
            EmsFields::ASSET_CONFIG_DISPOSITION => $disposition,
        ]));
    }

    /**
     * http://blog.alterphp.com/2012/08/how-to-deal-with-asynchronous-request.html.
     */
    private function closeSession(Request $request)
    {
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if ($session->isStarted()) {
            $session->save();
        }
    }
}
