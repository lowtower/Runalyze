<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My\Tools;

use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Route as RouteEntity;
use Runalyze\Bundle\CoreBundle\Entity\RouteRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\Tools\BackupExportType;
use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/my/tools/backup')]
class BackupToolController extends AbstractController
{
    public function __construct(
        protected ManagerRegistry $doctrine,
        private readonly MessageBusInterface $messageBus,
        private readonly RequestStack $requestStack
    ) {
    }

    protected function getPathToBackupFiles(): string
    {
        return $this->getParameter('app.data_directory') . '/backup-tool/backup/';
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

    #[Route(path: '/download/{filename}', name: 'tools-backup-download', requirements: [
        'filename' => \Symfony\Component\Routing\Requirement\Requirement::CATCH_ALL,
    ], methods: ['GET'])]
    public function downloadBackupAction(string $filename, ?Account $account = null): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $fileSystem = new Filesystem();
        $fileHandler = new FilenameHandler($account->getId());
        $filePath = $this->getPathToBackupFiles();
        $internalFilename = $fileHandler->transformPublicToInternalFilename($filename);

        if (! $fileSystem->exists($filePath . $internalFilename)) {
            throw $this->createNotFoundException();
        }

        if (! $fileHandler->validateInternalFilename($internalFilename)) {
            throw $this->createAccessDeniedException();
        }

        $response = new BinaryFileResponse($filePath . $internalFilename);
        $response->trustXSendfileTypeHeader();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            iconv('UTF-8', 'ASCII//TRANSLIT', $filename)
        );

        return $response;
    }

    #[Route(path: '', name: 'tools-backup', methods: ['GET', 'POST'])]
    public function backupAction(?Account $account = null, ?Request $request = null): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(BackupExportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formdata = $request->request->all($form->getName());
            $this->messageBus->dispatch(new BackupGenerationMessage(
                $account->getId(),
                $formdata['fileFormat']
            ));

            // The template only checks whether this flash message is present, never its value.
            $this->addFlash('runalyze.backupjob.created', true);

            return $this->redirectToRoute('tools-backup');
        }

        /** @var RouteRepository $repository */
        $repository = $this->doctrine
            ->getManager()
            ->getRepository(RouteEntity::class);
        $lockedRoutes = $repository->accountHasLockedRoutes($account);
        /** @var TrainingRepository $repository */
        $repository = $this->doctrine
            ->getManager()
            ->getRepository(Training::class);
        $hasLockedTrainings = $repository->accountHasLockedTrainings($account);

        $fileHandler = new FilenameHandler($account->getId());
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->getPathToBackupFiles())
            ->filter(fn (\SplFileInfo $file): bool => $fileHandler->validateInternalFilename($file->getFilename()))
            ->sort(fn (\SplFileInfo $a, \SplFileInfo $b): int => $b->getMTime() - $a->getMTime());

        return $this->render('tools/backup/export.html.twig', [
            'backupjobWasCreated' => $this->getFlashBagAwareSession()
                ->getFlashBag()
                ->get('runalyze.backupjob.created'),
            'hasFiles' => $finder->count() > 0,
            'files' => $finder->getIterator(),
            'hasLocks' => ($lockedRoutes || $hasLockedTrainings),
            'form' => $form->createView(),
        ]);
    }
}
