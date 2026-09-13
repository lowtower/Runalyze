<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteExpiredNotificationsCommand extends Command
{
    public function __construct(
        private ManagerRegistry $doctrine
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('runalyze:notifications:clear')
            ->setDescription('Delete all expired notifications')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->doctrine->getRepository(\Runalyze\Bundle\CoreBundle\Entity\Notification::class);
        $numRemoved = $notificationRepository->removeExpiredNotifications();

        $output->writeln(sprintf('<info>%u notifications have been removed.</info>', $numRemoved));
        $output->writeln('');

        return 0;
    }
}
