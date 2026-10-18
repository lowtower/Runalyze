<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\CleanupRegistrationsCommand;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\AccountRepository;
use Symfony\Component\Console\Tester\CommandTester;

final class CleanupRegistrationsCommandTest extends TestCase
{
    // Stub by default. Tests that need expects() on the repository build
    // their own mock and pass it to createCommand().
    private AccountRepository&Stub $accountRepository;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createStub(AccountRepository::class);
    }

    /**
     * Replaces ->with(Account::class): a stub has no with(), so the check
     * is an assertion inside the callback.
     */
    private function createDoctrine(AccountRepository $accountRepository): ManagerRegistry
    {
        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine
            ->method('getRepository')
            ->willReturnCallback(function (string $className) use ($accountRepository): AccountRepository {
                $this->assertSame(Account::class, $className);

                return $accountRepository;
            });

        return $doctrine;
    }

    private function createCommand(?AccountRepository $accountRepository = null): CleanupRegistrationsCommand
    {
        return new CleanupRegistrationsCommand($this->createDoctrine($accountRepository ?? $this->accountRepository));
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
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository
            ->expects(self::once())
            ->method('deleteNotActivatedAccounts')
            ->with(7)
            ->willReturn([1, 2, 3]);

        $tester = new CommandTester($this->createCommand($accountRepository));
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        self::assertStringContainsString('Delete all not activated accounts older than 7 days', $display);
        self::assertStringContainsString('3 deleted accounts', $display);
    }

    public function testExecuteUsesProvidedDaysArgument(): void
    {
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository
            ->expects(self::once())
            ->method('deleteNotActivatedAccounts')
            ->with(30)
            ->willReturn([1]);

        $tester = new CommandTester($this->createCommand($accountRepository));
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
        $accountRepository = $this->createMock(AccountRepository::class);
        $accountRepository
            ->expects(self::once())
            ->method('deleteNotActivatedAccounts')
            ->with(7)
            ->willReturn([]);

        $tester = new CommandTester($this->createCommand($accountRepository));
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
