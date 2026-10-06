<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[\Symfony\Component\Console\Attribute\AsCommand(name: 'runalyze:notifications:clear', description: 'Delete all expired notifications')]
class DeleteExpiredNotificationsCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $doctrine
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->doctrine->getRepository(Notification::class);
        $numRemoved = $notificationRepository->removeExpiredNotifications();

        $output->writeln(sprintf('<info>%u notifications have been removed.</info>', $numRemoved));
        $output->writeln('');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
