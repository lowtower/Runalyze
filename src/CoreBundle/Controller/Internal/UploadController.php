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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route(path: '/_internal/upload')]
class UploadController extends AbstractController
{
    /**
     * @throws AccessDeniedException
     */
    #[Route(path: '', name: 'internal-activity-upload', methods: ['GET', 'POST'])]
    public function uploadAction(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($request->files->has('qqfile')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('qqfile');
            $newFileName = basename(str_replace('\\', '/', str_replace(';', '_-_', $file->getClientOriginalName())));

            if (! $this->isValidFileName($newFileName)) {
                return new JsonResponse([
                    'error' => 'The given file name is not valid.',
                ]);
            }

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
    #[Route(path: '/tcx', name: 'internal-activity-upload-tcx', methods: ['GET', 'POST'])]
    public function ajaxSaveTcxAction(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $activityId = $request->request->get('activityId');
        $data = $request->request->get('data');

        if (! is_string($activityId) || ! is_string($data) || $activityId === '' || $data === '') {
            return new JsonResponse([
                'error' => 'No data given.',
            ]);
        }

        if (! $this->isValidFileName($activityId)) {
            return new JsonResponse([
                'error' => 'The given activity id is not valid.',
            ]);
        }

        $filesystem = new Filesystem();
        $fileName = $activityId . '.tcx';

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
                $data
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

    private function isValidFileName(string $fileName): bool
    {
        return
            $fileName !== ''
            && ! in_array($fileName, ['.', '..'], true)
            && $fileName === basename($fileName)
            && ! str_contains($fileName, '\\')
            && ! str_contains($fileName, "\0")
        ;
    }
}
