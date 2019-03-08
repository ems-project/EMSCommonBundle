<?php

namespace EMS\CommonBundle\Controller;

use EMS\CommonBundle\Storage\NotFoundException;
use EMS\CommonBundle\Storage\StorageManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileController extends AbstractController
{
    /**
     * @var StorageManager
     */
    private $storageManager;

    /**
     * @param StorageManager $storageManager
     */
    public function __construct(StorageManager $storageManager)
    {
        $this->storageManager = $storageManager;
    }

    /**
     * @param Request $request
     * @param string  $sha1
     *
     * @return Response
     */
    public function view(Request $request, string $sha1)
    {
        $this->closeSession($request);

        return $this->getFile($request, $sha1, ResponseHeaderBag::DISPOSITION_INLINE);

    }

    /**
     * @param Request $request
     * @param string  $sha1
     *
     * @return Response
     */
    public function download(Request $request, string $sha1)
    {
        $this->closeSession($request);

        return $this->getFile($request, $sha1, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    /**
     * @param Request $request
     * @param string  $sha1
     * @param string  $disposition
     *
     * @return Response
     */
    private function getFile(Request $request, string $sha1, string $disposition): Response
    {
        $cacheResponse = $this->cacheResponse($request, $sha1);

        if ($cacheResponse) {
            return $cacheResponse;
        }

        //TODO: name should be part of the route and the mimetype hidden in the configHash
        $name = $request->query->get('name', 'upload.bin');
        $type = $request->query->get('type', 'application/bin');

        try {
            $response = $this->createResponse($sha1, $type, $name, $disposition);

        }
        catch (\Exception $e) {
            if(\substr($type, 0, 5) === 'image') {
                $response = new BinaryFileResponse($this->storageManager->getPublicImage('image-not-found.svg'));
                $response->setPublic();
            } else {
                $response = new Response('File not found', 404);
            }
        }

        return $response;
    }

    /**
     * @param string
     * @param string
     * @param string
     * @param string
     *
     * @return Response
     */
    private function createResponse(string $sha1, string $name, string $type, string $disposition=ResponseHeaderBag::DISPOSITION_INLINE)
    {
        $response = null;
        try {

            $handler = $this->storageManager()->getResource($sha1);

            if(!$handler){
                throw new NotFoundHttpException('Impossible to find the item corresponding to this id: '.$sha1);
            }

            $response = new StreamedResponse(
                function () use ($handler) {
                    while (!feof($handler)) {
                        print fread($handler, 8192);
                    }
                }, 200, [
                'Content-Disposition' => $disposition.'; '.HeaderUtils::toString(array('filename' => $name), ';'),
                'Content-Type' => $type,
            ]);

        } catch (NotFoundException $ex) {
            throw new NotFoundHttpException('file not found');
        }

        $response->setEtag($sha1);
        $response->setPublic();

        return $response;
    }

    /**
     * @param Request $request
     * @param string  $sha1
     *
     * @return bool|Response
     */
    private function cacheResponse(Request $request, string $sha1)
    {
        $response = new Response();
        $response->setPublic();
        $response->setEtag($sha1);

        if ($response->isNotModified($request)) {
            return $response; //cached
        }

        return false;
    }

    /**
     * http://blog.alterphp.com/2012/08/how-to-deal-with-asynchronous-request.html
     *
     * @param Request $request
     */
    private function closeSession(Request $request)
    {
        $session = $request->getSession();

        if ($session && $session->isStarted()) {
            $session->save();
        }
    }
}