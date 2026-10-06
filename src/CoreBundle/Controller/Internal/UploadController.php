<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Controller\Internal;

use Normalizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[\Symfony\Component\Routing\Attribute\Route(path: '/_internal/upload')]
class UploadController extends AbstractController
{
    /**
     * @throws AccessDeniedException
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '', name: 'internal-activity-upload')]
    public function uploadAction(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($request->files->has('qqfile')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('qqfile');
            $newFileName = str_replace(';', '_-_', $file->getClientOriginalName());

            if (class_exists('Normalizer')) {
                $normalizedFileName = Normalizer::normalize($newFileName);

                if ($normalizedFileName === false) {
                    return new JsonResponse([
                        'error' => 'The given file name is not valid UTF-8 and could not be normalized.',
                    ]);
                }

                $newFileName = $normalizedFileName;
            }

            try {
                $file->move(
                    $this->getParameter('app.data_directory') . '/import',
                    $newFileName
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

        return new JsonResponse([
            'error' => 'No file given.',
        ]);
    }

    /**
     * @throws AccessDeniedException
     */
    #[\Symfony\Component\Routing\Attribute\Route(path: '/tcx', name: 'internal-activity-upload-tcx')]
    public function ajaxSaveTcxAction(Request $request): JsonResponse
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
            $normalizedFileName = Normalizer::normalize($fileName);

            if ($normalizedFileName === false) {
                return new JsonResponse([
                    'error' => 'The given file name is not valid UTF-8 and could not be normalized.',
                ]);
            }

            $fileName = $normalizedFileName;
        }

        try {
            $filesystem->appendToFile(
                $this->getParameter('app.data_directory') . '/import/' . $fileName,
                $request->request->get('data')
            );

            return new JsonResponse([
                'success' => true,
            ]);
        } catch (IOException $ioException) {
            return new JsonResponse([
                'error' => $ioException->getMessage(),
            ]);
        }
    }
}
