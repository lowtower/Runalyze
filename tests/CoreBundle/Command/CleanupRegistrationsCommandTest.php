<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\CleanupRegistrationsCommand;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Symfony\Component\Console\Tester\CommandTester;

final class CleanupRegistrationsCommandTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;

    private AccountRepository&MockObject $accountRepository;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);

        $this->doctrine
            ->method('getRepository')
            ->with(Account::class)
            ->willReturn($this->accountRepository);
    }

    private function createCommand(): CleanupRegistrationsCommand
    {
        return new CleanupRegistrationsCommand($this->doctrine);
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->createCommand();

        self::assertSame('runalyze:cleanup:registrations', $command->getName());
        self::assertSame(
            'Delete not activated account (default: older than 7 days).',
            $command->getDescription()
        );

        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('days'));
        self::assertFalse($definition->getArgument('days')->isRequired());
    }

    public function testExecuteUsesDefaultOfSevenDaysWhenArgumentOmitted(): void
    {
        $this->accountRepository
            ->expects(self::once())
            ->method('deleteNotActivatedAccounts')
            ->with(7)
            ->willReturn([1, 2, 3]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('Delete all not activated accounts older than 7 days', $display);
        self::assertStringContainsString('3 deleted accounts', $display);
    }

    public function testExecuteUsesProvidedDaysArgument(): void
    {
        $this->accountRepository
            ->expects(self::once())
            ->method('deleteNotActivatedAccounts')
            ->with(30)
            ->willReturn([1]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        self::assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('Delete all not activated accounts older than 30 days', $display);
        self::assertStringContainsString('1 deleted accounts', $display);
    }

    public function testExecuteTreatsExplicitZeroDaysAsFalsyAndFallsBackToDefault(): void
    {
        $this->accountRepository
            ->expects(self::once())
            ->method('deleteNotActivatedAccounts')
            ->with(7)
            ->willReturn([]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '0',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Delete all not activated accounts older than 7 days', $tester->getDisplay());
    }

    public function testExecuteReportsZeroDeletedAccountsWhenNoneMatch(): void
    {
        $this->accountRepository
            ->method('deleteNotActivatedAccounts')
            ->willReturn([]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('0 deleted accounts', $tester->getDisplay());
    }

    public function testExecuteAlwaysReturnsSuccessExitCode(): void
    {
        $this->accountRepository
            ->method('deleteNotActivatedAccounts')
            ->willReturn([1, 2]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '14',
        ]);

        self::assertSame(0, $exitCode);
    }
}
