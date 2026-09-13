<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My\Tools;

use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonBackupAnalyzer;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonImporter;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/my/tools/backup-import")
 */
class JsonImportToolController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;

    private ParameterBagInterface $parameterBag;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->parameterBag = $parameterBag;
    }

    protected function getImportFilePath(): string
    {
        return $this->parameterBag->get('app.data_directory') . '/backup-tool/import/';
    }

    /**
     * @Route("/upload", name="tools-backup-json-upload")
     */
    public function backupUploadAction(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $backupFile = $request->files->get('qqfile');

        if ($backupFile === null) {
            return $this->json([
                'error' => 'File upload did not work.',
            ]);
        }

        if (! FilenameHandler::validateImportFileExtension($backupFile->getClientOriginalName())) {
            return $this->json([
                'error' => 'Wrong file extension.',
            ]);
        }

        try {
            $backupFile->move($this->getImportFilePath(), $backupFile->getClientOriginalName());
        } catch (FileException $fileException) {
            return $this->json([
                'error' => 'Moving file did not work. Set chmod 777 for data/backup-tool/import/ ' . $fileException->getMessage(),
            ]);
        }

        $this->get('session')
            ->getFlashBag()
            ->set('json-import.file', $backupFile->getClientOriginalName());

        return $this->json([
            'success' => true,
        ]);
    }

    /**
     * @Route("/import", name="tools-backup-json-import")
     */
    public function backupImportAction(): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $sessionFlashBag = $this->get('session')
            ->getFlashBag();

        if (! $sessionFlashBag->has('json-import.file')) {
            return $this->redirectToRoute('tools-backup-json');
        }

        $filePath = $this->getImportFilePath();
        $filename = $sessionFlashBag->get('json-import.file')[0];
        $fileInfo = new \SplFileInfo($filePath . $filename);
        $analyzer = new JsonBackupAnalyzer($filePath . $filename, $this->parameterBag->get('RUNALYZE_VERSION'));

        if (! $analyzer->fileIsOkay()) {
            (new Filesystem())->remove($filePath . $filename);

            return $this->render('tools/backup/import_bad_file.html.twig', [
                'file' => $fileInfo,
                'versionIsOkay' => $analyzer->versionIsOkay(),
                'runalyzeVersion' => $this->parameterBag->get('RUNALYZE_VERSION'),
                'runalyzeVersionFile' => $analyzer->fileVersion(),
            ]);
        }

        $sessionFlashBag->set('json-import.file', $filename);

        return $this->render('tools/backup/import_form.html.twig', [
            'file' => $fileInfo,
            'numActivities' => $analyzer->count('runalyze_training'),
            'numBodyValues' => $analyzer->count('runalyze_user'),
        ]);
    }

    /**
     * @Route("/import/do", name="tools-backup-json-import-do")
     */
    public function backupImportDoAction(Request $request, Account $account): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);

        $sessionFlashBag = $this->get('session')
            ->getFlashBag();

        if (! $sessionFlashBag->has('json-import.file')) {
            return $this->redirectToRoute('tools-backup-json');
        }

        $filePath = $this->getImportFilePath();
        $filename = $sessionFlashBag->get('json-import.file')[0];

        $importer = new JsonImporter(
            $filePath . $filename,
            \DB::getInstance(),
            $account->getId(),
            $this->parameterBag->get('app.database_prefix')
        );

        if ($request->request->get('delete_trainings')) {
            $importer->deleteOldActivities();
        }

        if ($request->request->get('delete_user_data')) {
            $importer->deleteOldBodyValues();
        }

        $importer->enableOverwritingConfig($request->request->get('overwrite_config'));
        $importer->enableOverwritingDataset($request->request->get('overwrite_dataset'));
        $importer->enableOverwritingPlugins($request->request->get('overwrite_plugin'));
        $importer->importData();

        return $this->render('tools/backup/import_finish.html.twig', [
            'results' => $importer->resultsAsString(),
        ]);
    }

    /**
     * @Route("", name="tools-backup-json")
     */
    public function uploadFormAction(): \Symfony\Component\HttpFoundation\Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('tools/backup/upload_form.html.twig', [
            'runalyzeVersion' => $this->parameterBag->get('RUNALYZE_VERSION'),
        ]);
    }
}
