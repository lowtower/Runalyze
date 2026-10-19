<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\MessageHandler;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\BackupReadyMessage;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonBackup;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\SqlBackup;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[\Symfony\Component\Messenger\Attribute\AsMessageHandler]
class BackupGenerationMessageHandler
{
    private readonly string $dataDirectory;

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly AccountMailer $accountMailer,
        string $dataDirectory,
        private readonly string $databasePrefix,
        private readonly string $runalyzeVersion
    ) {
        $this->dataDirectory = realpath($dataDirectory);
    }

    public function __invoke(BackupGenerationMessage $message): void
    {
        $account = $this->accountRepository->find($message->getAccountId());

        if ($account === null) {
            return;
        }

        if (! defined('RUNALYZE')) {
            new \FrontendShared(true, null, $this->parameterBag);
        }

        $fileHandler = new FilenameHandler($message->getAccountId());
        $fileHandler->setRunalyzeVersion($this->runalyzeVersion);

        if ($message->getExportType() === 'json') {
            $backup = new JsonBackup(
                $this->getPathToBackupFiles() . $fileHandler->generateInternalFilename(FilenameHandler::JSON_FORMAT),
                $message->getAccountId(),
                \DB::getInstance(),
                $this->databasePrefix,
                $this->runalyzeVersion
            );
        } else {
            $backup = new SqlBackup(
                $this->getPathToBackupFiles() . $fileHandler->generateInternalFilename(FilenameHandler::SQL_FORMAT),
                $message->getAccountId(),
                \DB::getInstance(),
                $this->databasePrefix,
                $this->runalyzeVersion
            );
        }

        $backup->run();

        $this->notificationRepository->save(
            Notification::createFromMessage(new BackupReadyMessage(), $account)
        );

        $this->accountMailer->sendBackupGeneratedTo($account);

        gc_collect_cycles();
    }

    private function getPathToBackupFiles(): string
    {
        return $this->dataDirectory . '/backup-tool/backup/';
    }
}
