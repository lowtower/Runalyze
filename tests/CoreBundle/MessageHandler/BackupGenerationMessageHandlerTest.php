<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\MessageHandler;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Runalyze\Bundle\CoreBundle\Entity\NotificationRepository;
use Runalyze\Bundle\CoreBundle\Message\BackupGenerationMessage;
use Runalyze\Bundle\CoreBundle\MessageHandler\BackupGenerationMessageHandler;
use Runalyze\Bundle\CoreBundle\Services\AccountMailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BackupGenerationMessageHandlerTest extends TestCase
{
    private AccountRepository&MockObject $accountRepositoryMock;

    private NotificationRepository&MockObject $notificationRepositoryMock;

    private ParameterBagInterface&MockObject $parameterBagMock;

    private AccountMailer&MockObject $accountMailerMock;

    protected function setUp(): void
    {
        $this->accountRepositoryMock = $this->createMock(AccountRepository::class);
        $this->notificationRepositoryMock = $this->createMock(NotificationRepository::class);
        $this->parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $this->accountMailerMock = $this->createMock(AccountMailer::class);
    }

    private function createHandler(): BackupGenerationMessageHandler
    {
        return new BackupGenerationMessageHandler(
            $this->accountRepositoryMock,
            $this->notificationRepositoryMock,
            $this->parameterBagMock,
            $this->accountMailerMock,
            sys_get_temp_dir(),
            'runalyze_',
            '3.x-test'
        );
    }

    public function testInvokeReturnsEarlyWhenAccountIsNotFound(): void
    {
        $this->accountRepositoryMock->method('find')
            ->with(42)
            ->willReturn(null);

        $this->notificationRepositoryMock->expects($this->never())
            ->method('save');
        $this->accountMailerMock->expects($this->never())
            ->method('sendBackupGeneratedTo');

        $handler = $this->createHandler();
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
