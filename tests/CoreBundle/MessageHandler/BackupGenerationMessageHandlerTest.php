<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\MessageHandler;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;
use Runalyze\Bundle\CoreBundle\MessageHandler\BackupGenerationMessageHandler;
use Runalyze\Bundle\CoreBundle\Repository\AccountRepository;
use Runalyze\Bundle\CoreBundle\Repository\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class BackupGenerationMessageHandlerTest extends TestCase
{
    // Stubs by default. The one test that needs expects() builds its own
    // mocks and passes them to createHandler().
    private AccountRepository&Stub $accountRepositoryMock;

    private NotificationRepository&Stub $notificationRepositoryMock;

    private ParameterBagInterface&Stub $parameterBagMock;

    private AccountMailer&Stub $accountMailerMock;

    protected function setUp(): void
    {
        $this->accountRepositoryMock = $this->createStub(AccountRepository::class);
        $this->notificationRepositoryMock = $this->createStub(NotificationRepository::class);
        $this->parameterBagMock = $this->createStub(ParameterBagInterface::class);
        $this->accountMailerMock = $this->createStub(AccountMailer::class);
    }

    private function createHandler(
        ?AccountRepository $accountRepository = null,
        ?NotificationRepository $notificationRepository = null,
        ?AccountMailer $accountMailer = null
    ): BackupGenerationMessageHandler {
        return new BackupGenerationMessageHandler(
            $accountRepository ?? $this->accountRepositoryMock,
            $notificationRepository ?? $this->notificationRepositoryMock,
            $this->parameterBagMock,
            $accountMailer ?? $this->accountMailerMock,
            sys_get_temp_dir(),
            'runalyze_',
            '3.x-test'
        );
    }

    public function testInvokeReturnsEarlyWhenAccountIsNotFound(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository
            ->expects($this->once())
            ->method('find')
            ->with(42)
            ->willReturn(null);

        $notificationRepository = $this->createMock(NotificationRepository::class);
        $notificationRepository->expects($this->never())
            ->method('save');

        $accountMailer = $this->createMock(AccountMailer::class);
        $accountMailer->expects($this->never())
            ->method('sendBackupGeneratedTo');

        $handler = $this->createHandler($accountRepository, $notificationRepository, $accountMailer);
        $message = new BackupGenerationMessage(42, 'json');

        // No exception and no interaction with the heavy backup
        // collaborators should occur here.
        $handler($message);
    }

    public function testGetPathToBackupFilesAppendsExpectedSubdirectory(): void
    {
        $dataDirectory = sys_get_temp_dir();
        $handler = $this->createHandler();

        $reflection = new \ReflectionObject($handler);
        $method = $reflection->getMethod('getPathToBackupFiles');

        $result = $method->invoke($handler);

        $this->assertSame(realpath($dataDirectory) . '/backup-tool/backup/', $result);
    }
}
