<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\MessageHandler;

use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\BackupReadyMessage;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonBackup;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\SqlBackup;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class BackupGenerationMessageHandler implements MessageHandlerInterface
{
    private AccountRepository $accountRepository;

    private NotificationRepository $notificationRepository;

    private ParameterBagInterface $parameterBag;

    private AccountMailer $accountMailer;

    private string $dataDirectory;

    private string $databasePrefix;

    private string $runalyzeVersion;

    public function __construct(
        AccountRepository $accountRepository,
        NotificationRepository $notificationRepository,
        ParameterBagInterface $parameterBag,
        AccountMailer $accountMailer,
        string $dataDirectory,
        string $databasePrefix,
        string $runalyzeVersion
    ) {
        $this->accountRepository = $accountRepository;
        $this->notificationRepository = $notificationRepository;
        $this->parameterBag = $parameterBag;
        $this->accountMailer = $accountMailer;
        $this->dataDirectory = realpath($dataDirectory);
        $this->databasePrefix = $databasePrefix;
        $this->runalyzeVersion = $runalyzeVersion;
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
