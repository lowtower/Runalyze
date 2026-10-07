<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My\Tools;

use LogicException;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonBackupAnalyzer;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonImporter;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[\Symfony\Component\Routing\Attribute\Route(path: '/my/tools/backup-import')]
class JsonImportToolController extends AbstractController
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ParameterBagInterface $parameterBag,
        private readonly RequestStack $requestStack
    ) {
    }

    protected function getImportFilePath(): string
    {
        return $this->parameterBag->get('app.data_directory') . '/backup-tool/import/';
    }

    /**
     * @throws LogicException if the current session is not a flash-bag-capable Session instance
     */
    private function getFlashBagAwareSession(): Session
    {
        $session = $this->requestStack->getSession();

        if (! $session instanceof Session) {
            throw new LogicException(sprintf('Expected an instance of %s, got %s.', Session::class, get_debug_type($session)));
        }

        return $session;
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/upload', name: 'tools-backup-json-upload')]
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

        $this->getFlashBagAwareSession()
            ->getFlashBag()
            ->set('json-import.file', $backupFile->getClientOriginalName());

        return $this->json([
            'success' => true,
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '/import', name: 'tools-backup-json-import')]
    public function backupImportAction(): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $sessionFlashBag = $this->getFlashBagAwareSession()
            ->getFlashBag();

        if (! $sessionFlashBag->has('json-import.file')) {
            return $this->redirectToRoute('tools-backup-json');
        }

        $filePath = $this->getImportFilePath();
        $filename = $sessionFlashBag->get('json-import.file')[0];
        $fileInfo = new \SplFileInfo($filePath . $filename);
        $analyzer = new JsonBackupAnalyzer($filePath . $filename, $this->parameterBag->get('RUNALYZE_VERSION'));

        if (! $analyzer->fileIsOkay()) {
            new Filesystem()->remove($filePath . $filename);

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

    #[\Symfony\Component\Routing\Attribute\Route(path: '/import/do', name: 'tools-backup-json-import-do')]
    public function backupImportDoAction(Request $request, ?Account $account = null): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $sessionFlashBag = $this->getFlashBagAwareSession()
            ->getFlashBag();

        if (! $sessionFlashBag->has('json-import.file')) {
            return $this->redirectToRoute('tools-backup-json');
        }

        $Frontend = new \Frontend(true, $this->tokenStorage, $this->parameterBag);

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

        $importer->enableOverwritingConfig((bool) $request->request->get('overwrite_config'));
        $importer->enableOverwritingDataset((bool) $request->request->get('overwrite_dataset'));
        $importer->enableOverwritingPlugins((bool) $request->request->get('overwrite_plugin'));
        $importer->importData();

        return $this->render('tools/backup/import_finish.html.twig', [
            'results' => $importer->resultsAsString(),
        ]);
    }

    #[\Symfony\Component\Routing\Attribute\Route(path: '', name: 'tools-backup-json')]
    public function uploadFormAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('tools/backup/upload_form.html.twig', [
            'runalyzeVersion' => $this->parameterBag->get('RUNALYZE_VERSION'),
        ]);
    }
}
