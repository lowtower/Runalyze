<?php

namespace Runalyze\Bundle\CoreBundle\Queue\Receiver;

use Bernard\Message\DefaultMessage;
use Runalyze\Bundle\CoreBundle\Component\Notifications\Message\BackupReadyMessage;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FilenameHandler;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\JsonBackup;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\SqlBackup;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BackupReceiver
{
    private ContainerInterface $container;

    private ParameterBagInterface $parameterBag;

    public function __construct(ContainerInterface $container, ParameterBagInterface $parameterBag)
    {
        $this->container = $container;
        $this->parameterBag = $parameterBag;
    }

    protected function getPathToBackupFiles(): string
    {
        return $this->parameterBag->get('app.data_directory') . '/backup-tool/backup/';
    }

    public function userBackup(DefaultMessage $message)
    {
        $Frontend = new \FrontendShared(true);

        $fileHandler = new FilenameHandler($message->get('accountid'));
        $fileHandler->setRunalyzeVersion($this->container->getParameter('RUNALYZE_VERSION'));

        /** @var Account $account */
        $account = $this->container->get('doctrine.orm.entity_manager')
            ->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Account::class)
            ->find($message->get('accountid'));

        if ($message->get('export-type') == 'json') {
            $Backup = new JsonBackup(
                $this->getPathToBackupFiles() . $fileHandler->generateInternalFilename(FilenameHandler::JSON_FORMAT),
                $message->get('accountid'),
                \DB::getInstance(),
                $this->container->getParameter('app.database_prefix'),
                $this->container->getParameter('RUNALYZE_VERSION')
            );
            $Backup->run();
        } else {
            $Backup = new SqlBackup(
                $this->getPathToBackupFiles() . $fileHandler->generateInternalFilename(FilenameHandler::SQL_FORMAT),
                $message->get('accountid'),
                \DB::getInstance(),
                $this->container->getParameter('app.database_prefix'),
                $this->container->getParameter('RUNALYZE_VERSION')
            );
            $Backup->run();
        }

        $this->container->get('doctrine')
            ->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Notification::class)
            ->save(
                Notification::createFromMessage(new BackupReadyMessage(), $account)
            );
        gc_collect_cycles();
    }
}
