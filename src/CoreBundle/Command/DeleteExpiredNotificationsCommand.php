<?php

namespace Runalyze\Bundle\CoreBundle\Command;

use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteExpiredNotificationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('runalyze:notifications:clear')
            ->setDescription('Delete all expired notifications')
        ;
    }

    /**
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->getContainer()
            ->get('doctrine')
            ->getRepository('CoreBundle:Notification');
        $numRemoved = $notificationRepository->removeExpiredNotifications();

        $output->writeln(sprintf('<info>%u notifications have been removed.</info>', $numRemoved));
        $output->writeln('');

        return 0;
    }
}
