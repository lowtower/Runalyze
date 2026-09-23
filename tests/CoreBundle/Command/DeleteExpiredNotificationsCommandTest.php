<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\DeleteExpiredNotificationsCommand;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Symfony\Component\Console\Tester\CommandTester;

final class DeleteExpiredNotificationsCommandTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;

    private NotificationRepository&MockObject $notificationRepository;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->notificationRepository = $this->createMock(NotificationRepository::class);

        $this->doctrine
            ->method('getRepository')
            ->with(Notification::class)
            ->willReturn($this->notificationRepository);
    }

    private function createCommand(): DeleteExpiredNotificationsCommand
    {
        return new DeleteExpiredNotificationsCommand($this->doctrine);
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->createCommand();

        self::assertSame('runalyze:notifications:clear', $command->getName());
        self::assertSame('Delete all expired notifications', $command->getDescription());
    }

    public function testExecuteReportsNumberOfRemovedNotifications(): void
    {
        $this->notificationRepository
            ->expects(self::once())
            ->method('removeExpiredNotifications')
            ->willReturn(3);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString(
            '3 notifications have been removed.',
            $tester->getDisplay()
        );
    }

    public function testExecuteReportsZeroWhenNothingWasExpired(): void
    {
        $this->notificationRepository
            ->method('removeExpiredNotifications')
            ->willReturn(0);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString(
            '0 notifications have been removed.',
            $tester->getDisplay()
        );
    }

    public function testExecuteFetchesRepositoryForNotificationEntity(): void
    {
        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(Notification::class)
            ->willReturn($this->notificationRepository);

        $this->notificationRepository
            ->method('removeExpiredNotifications')
            ->willReturn(1);

        $tester = new CommandTester($this->createCommand());
        $tester->execute([]);
    }
}
