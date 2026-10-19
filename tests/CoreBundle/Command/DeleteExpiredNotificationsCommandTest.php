<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\DeleteExpiredNotificationsCommand;
use Runalyze\Bundle\CoreBundle\Entity\Notification;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Symfony\Component\Console\Tester\CommandTester;

final class DeleteExpiredNotificationsCommandTest extends TestCase
{
    // Stub by default. Tests that need expects() build their own mocks
    // and pass them to createCommand().
    private NotificationRepository&Stub $notificationRepository;

    protected function setUp(): void
    {
        $this->notificationRepository = $this->createStub(NotificationRepository::class);
    }

    /**
     * Replaces ->with(Notification::class): a stub has no with(), so the
     * check is an assertion inside the callback.
     */
    private function createDoctrine(NotificationRepository $notificationRepository): ManagerRegistry
    {
        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine
            ->method('getRepository')
            ->willReturnCallback(function (string $className) use ($notificationRepository): NotificationRepository {
                $this->assertSame(Notification::class, $className);

                return $notificationRepository;
            });

        return $doctrine;
    }

    private function createCommand(?NotificationRepository $notificationRepository = null, ?ManagerRegistry $doctrine = null): DeleteExpiredNotificationsCommand
    {
        $notificationRepository ??= $this->notificationRepository;

        return new DeleteExpiredNotificationsCommand($doctrine ?? $this->createDoctrine($notificationRepository));
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->createCommand();

        $this->assertSame('runalyze:notifications:clear', $command->getName());
        $this->assertSame('Delete all expired notifications', $command->getDescription());
    }

    public function testExecuteReportsNumberOfRemovedNotifications(): void
    {
        $notificationRepository = $this->createMock(NotificationRepository::class);
        $notificationRepository
            ->expects($this->once())
            ->method('removeExpiredNotifications')
            ->willReturn(3);

        $tester = new CommandTester($this->createCommand($notificationRepository));
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('3 notifications have been removed.', $tester->getDisplay());
    }

    public function testExecuteReportsZeroWhenNothingWasExpired(): void
    {
        $this->notificationRepository
            ->method('removeExpiredNotifications')
            ->willReturn(0);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('0 notifications have been removed.', $tester->getDisplay());
    }

    public function testExecuteFetchesRepositoryForNotificationEntity(): void
    {
        $this->notificationRepository
            ->method('removeExpiredNotifications')
            ->willReturn(1);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine
            ->expects($this->once())
            ->method('getRepository')
            ->with(Notification::class)
            ->willReturn($this->notificationRepository);

        $tester = new CommandTester($this->createCommand(doctrine: $doctrine));
        $tester->execute([]);
    }
}
