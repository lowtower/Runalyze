<?php

namespace Runalyze\Bundle\CoreBundle\Controller\My\Tools;

use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Route as RunalyzeRoute;
use Runalyze\Bundle\CoreBundle\Entity\RouteRepository;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Form\Tools\BackupExportType;
use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/my/tools/backup")
 */
class BackupToolController extends AbstractController
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    protected function getPathToBackupFiles(): string
    {
        return $this->getParameter('app.data_directory') . '/backup-tool/backup/';
    }

    /**
     * @Route("/download/{filename}", name="tools-backup-download", requirements={"filename"=".+"})
     */
    public function downloadBackupAction(string $filename, Account $account): BinaryFileResponse
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

    /**
     * @Route("", name="tools-backup")
     */
    public function backupAction(Account $account, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var RouteRepository $repository */
        $repository = $this->getDoctrine()
            ->getManager()
            ->getRepository(RunalyzeRoute::class);
        $lockedRoutes = $repository->accountHasLockedRoutes($account);
        /** @var TrainingRepository $repository */
        $repository = $this->getDoctrine()
            ->getManager()
            ->getRepository(Training::class);
        $hasLockedTrainings = $repository->accountHasLockedTrainings($account);

        $form = $this->createForm(BackupExportType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formdata = $request->request->get($form->getName());
            $this->messageBus->dispatch(new BackupGenerationMessage(
                $account->getId(),
                $formdata['fileFormat']
            ));
            $this->get('session')
                ->getFlashBag()
                ->set('runalyze.backupjob.created', true);
        }

        $fileHandler = new FilenameHandler($account->getId());
        $finder = new Finder();
        $finder
            ->files()
            ->in($this->getPathToBackupFiles())
            ->filter(fn (\SplFileInfo $file): bool => $fileHandler->validateInternalFilename($file->getFilename()))
            ->sort(fn (\SplFileInfo $a, \SplFileInfo $b): int|float => $b->getMTime() - $a->getMTime());

        return $this->render('tools/backup/export.html.twig', [
            'backupjobWasCreated' => $this->get('session')
                ->getFlashBag()
                ->get('runalyze.backupjob.created'),
            'hasFiles' => $finder->count() > 0,
            'files' => $finder->getIterator(),
            'hasLocks' => ($lockedRoutes || $hasLockedTrainings),
            'form' => $form->createView(),
        ]);
    }
}
