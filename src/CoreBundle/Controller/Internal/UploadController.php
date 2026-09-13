<?php

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/_internal/upload")
 */
class UploadController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    /**
     * @Route("", name="internal-activity-upload")
     */
    public function uploadAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($request->files->has('qqfile')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('qqfile');
            $newFileName = str_replace(';', '_-_', $file->getClientOriginalName());

            if (class_exists('Normalizer')) {
                $newFileName = \Normalizer::normalize($newFileName);
            }

            try {
                $file->move(
                    $this->getParameter('app.data_directory') . '/import',
                    $newFileName
                );

                return new JsonResponse([
                    'success' => true,
                ]);
            } catch (FileException $e) {
                return new JsonResponse([
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return new JsonResponse([
            'error' => 'No file given.',
        ]);
    }

    /**
     * @Route("/tcx", name="internal-activity-upload-tcx")
     */
    public function ajaxSaveTcxAction(Request $request): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (! $request->request->has('activityId') || ! $request->request->has('data')) {
            return new JsonResponse([
                'error' => 'No data given.',
            ]);
        }

        $filesystem = new Filesystem();
        $fileName = $request->request->get('activityId') . '.tcx';

        if (class_exists('Normalizer')) {
            $fileName = \Normalizer::normalize($fileName);
        }

        try {
            $filesystem->appendToFile(
                $this->getParameter('app.data_directory') . '/import/' . $fileName,
                $request->request->get('data')
            );

            return new JsonResponse([
                'success' => true,
            ]);
        } catch (FileException $fileException) {
            return new JsonResponse([
                'error' => $fileException->getMessage(),
            ]);
        }
    }
}
