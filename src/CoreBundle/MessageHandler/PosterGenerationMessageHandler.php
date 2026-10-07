<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\MessageHandler;

use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\PosterGeneratedMessage;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter\AbstractSvgToPngConverter;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter\InkscapeConverter;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\Converter\RsvgConverter;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\FileHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\GenerateJsonData;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\GeneratePoster;
use Runalyze\Bundle\CoreBundle\Component\Tool\Poster\PosterGenerationStateResolver;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\SportRepository;
use Runalyze\Bundle\CoreBundle\Message\PosterGenerationMessage;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Exception\ProcessFailedException;

#[AsMessageHandler]
class PosterGenerationMessageHandler
{
    private readonly string $dataDir;

    /**
     * @throws InvalidArgumentException if the data directory does not exist
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AccountRepository $accountRepository,
        private readonly SportRepository $sportRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly GenerateJsonData $generateJsonData,
        private readonly GeneratePoster $generatePoster,
        private readonly FileHandler $fileHandler,
        private readonly AccountMailer $accountMailer,
        private readonly PosterGenerationStateResolver $stateResolver,
        string $dataDirectory,
        private readonly string $rsvgPath,
        private readonly string $inkscapePath
    ) {
        $realDataDirectory = realpath($dataDirectory);

        if ($realDataDirectory === false) {
            throw new InvalidArgumentException(sprintf('Data directory "%s" does not exist.', $dataDirectory));
        }

        $this->dataDir = $realDataDirectory;
    }

    public function __invoke(PosterGenerationMessage $message): void
    {
        $account = $this->accountRepository->find($message->getAccountId());
        $sport = $this->sportRepository->find($message->getSportId());

        if ($account === null || $sport === null || $sport->getAccount()->getId() !== $account->getId()) {
            return;
        }

        try {
            $this->generateJsonData->createJsonFilesFor($account, $sport, $message->getYear());

            $generatedFiles = $this->generatePosters($message, $account, $sport);

            $state = $this->stateResolver->resolve($generatedFiles, count($message->getTypes()));
            $this->notificationRepository->save(
                Notification::createFromMessage(new PosterGeneratedMessage($state), $account)
            );

            if ($state !== PosterGeneratedMessage::STATE_FAILED) {
                $this->accountMailer->sendPosterGeneratedTo($account, $state);
            }
        } finally {
            $this->generateJsonData->deleteGeneratedFiles();
            gc_collect_cycles();
        }
    }

    private function generatePosters(PosterGenerationMessage $message, Account $account, Sport $sport): int
    {
        $jsonFiles = new Finder()->files()
            ->in($this->generateJsonData->getPathToJsonFiles());

        if ($jsonFiles->count() === 0) {
            return 0;
        }

        $generatedFiles = 0;

        foreach ($message->getTypes() as $type) {
            try {
                $this->generatePoster->buildCommand(
                    $type,
                    $this->generateJsonData->getPathToJsonFiles(),
                    $message->getYear(),
                    $account,
                    $sport,
                    $message->getTitle(),
                    $message->getBackgroundColor(),
                    $message->getTrackColor(),
                    $message->getTextColor(),
                    $message->getRaceColor()
                );

                $finalName = $this->fileHandler->buildFinalFileName($account, $sport, $message->getYear(), $type, $message->getSize());
                $finalFile = $this->exportDirectory() . $finalName;
                $converter = $this->getConverter($type);
                $converter->setHeight($message->getSize());
                $hashFinalFile = $this->exportDirectory() . md5($finalName) . '.png';
                $exitCode = $converter->callConverter($this->generatePoster->generate(), $hashFinalFile);

                if ($exitCode > 0) {
                    throw new RuntimeException('Error while converting with error-code=' . $exitCode);
                }

                $filesystem = new Filesystem();
                $filesystem->rename($hashFinalFile, $finalFile);

                if ($filesystem->exists($finalFile)) {
                    $generatedFiles++;
                }

                $this->generatePoster->deleteSvg();
            } catch (Exception $e) {
                $context = [
                    'type' => $type,
                    'exception' => $e,
                ];

                if ($e instanceof ProcessFailedException) {
                    $context['processErrorOutput'] = $e->getProcess()->getErrorOutput();
                }

                $this->logger->error('Poster creation failed', $context);
            }
        }

        return $generatedFiles;
    }

    private function getConverter(string $posterType): AbstractSvgToPngConverter
    {
        if ($posterType === 'circular') {
            return new InkscapeConverter($this->inkscapePath);
        }

        return new RsvgConverter($this->rsvgPath);
    }

    private function exportDirectory(): string
    {
        return $this->dataDir . '/poster/';
    }
}
