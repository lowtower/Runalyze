<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\InstallCheckCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class InstallCheckCommandTest extends TestCase
{
    // Stubs by default. The one test that needs expects() on the connection
    // builds its own mock and passes it in through a separate ManagerRegistry stub.
    private ParameterBagInterface&Stub $parameterBag;

    private ManagerRegistry&Stub $doctrine;

    private Connection&Stub $connection;

    private string $tempRootDirectory;

    private const array REQUIRED_WRITABLE_DIRECTORIES = [
        'data/backup-tool/backup/',
        'data/backup-tool/import/',
        'data/cache/',
        'data/import/',
        'data/poster/',
        'data/sessions/',
        'var/cache/',
        'var/log/',
        'var/poster/',
        'var/queue/',
        'var/tmp/',
    ];

    protected function setUp(): void
    {
        $this->parameterBag = $this->createStub(ParameterBagInterface::class);
        $this->doctrine = $this->createStub(ManagerRegistry::class);
        $this->connection = $this->createStub(Connection::class);

        $this->doctrine
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->tempRootDirectory = sys_get_temp_dir() . '/install-check-command-test-' . bin2hex(random_bytes(8));
        mkdir($this->tempRootDirectory, 0777, true);
        // This "app" subdirectory simulates the value InstallCheckCommand::directoriesAreWritable()
        // receives as kernel.project_dir (see configureKernelRootDir() below) - it is named
        // "app" only because that is the directory configureKernelRootDir() points the mock
        // at, not because of any remaining Symfony kernel.root_dir semantics.
        mkdir($this->tempRootDirectory . '/app', 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempRootDirectory)) {
            $this->removeDirectoryRecursively($this->tempRootDirectory);
        }
    }

    private function removeDirectoryRecursively(string $directory): void
    {
        $items = scandir($directory);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectoryRecursively($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }

    /**
     * Directories must be created under $tempRootDirectory . '/app', not directly under
     * $tempRootDirectory, because 'app' is exactly the path configureKernelRootDir() mocks
     * kernel.project_dir to resolve to - and InstallCheckCommand::directoriesAreWritable()
     * checks each entry relative to that parameter's value, not relative to some directory
     * above it.
     *
     * @param string[] $directoriesToCreate
     */
    private function createRequiredDirectories(array $directoriesToCreate): void
    {
        foreach ($directoriesToCreate as $relativePath) {
            mkdir($this->tempRootDirectory . '/app/' . $relativePath, 0777, true);
        }
    }

    private function configureKernelRootDir(): void
    {
        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['kernel.project_dir', $this->tempRootDirectory . '/app'],
            ]);
    }

    private function mockPrefixQuery(int $matchingTableCount): void
    {
        $result = $this->createStub(Result::class);
        $result->method('rowCount')
            ->willReturn($matchingTableCount);

        $this->connection
            ->method('executeQuery')
            ->willReturn($result);
    }

    private function createCommand(?ManagerRegistry $doctrine = null): InstallCheckCommand
    {
        return new InstallCheckCommand($this->parameterBag, $doctrine ?? $this->doctrine);
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->createCommand();

        $this->assertSame('runalyze:install:check', $command->getName());
        $this->assertSame('Check requirements for installing RUNALYZE.', $command->getDescription());
    }

    public function testExecuteAllChecksPassProducesSuccessMessageAndZeroExitCode(): void
    {
        $this->configureKernelRootDir();
        $this->mockPrefixQuery(0);
        $this->createRequiredDirectories(self::REQUIRED_WRITABLE_DIRECTORIES);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Check PHP version', $display);
        $this->assertStringContainsString('Check that database prefix is not used', $display);
        $this->assertStringContainsString('Check that directories for cache, log and import are writable', $display);
        $this->assertStringContainsString('All requirements are fulfilled.', $display);
    }

    public function testExecuteReturnsFailureExitCodeWhenPrefixIsAlreadyUsed(): void
    {
        $this->configureKernelRootDir();
        $this->mockPrefixQuery(2);
        $this->createRequiredDirectories(self::REQUIRED_WRITABLE_DIRECTORIES);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(InstallCheckCommand::CHECK_FAILED, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Maybe RUNALYZE is already installed.', $display);
        $this->assertStringContainsString('Not all requirements are fulfilled, installation not possible.', $display);
    }

    public function testExecuteReportsWarningButStillSucceedsWhenADirectoryIsMissing(): void
    {
        $this->configureKernelRootDir();
        $this->mockPrefixQuery(0);

        $directoriesMinusOne = self::REQUIRED_WRITABLE_DIRECTORIES;
        array_pop($directoriesMinusOne);
        $this->createRequiredDirectories($directoriesMinusOne);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Respective directories in data/ and var/ must be writable.', $display);
        $this->assertStringContainsString('There were some warnings, installation may still be possible.', $display);
    }

    public function testExecutePrioritisesErrorMessageOverWarningWhenBothOccur(): void
    {
        $this->configureKernelRootDir();
        $this->mockPrefixQuery(1);

        $directoriesMinusOne = self::REQUIRED_WRITABLE_DIRECTORIES;
        array_pop($directoriesMinusOne);
        $this->createRequiredDirectories($directoriesMinusOne);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(InstallCheckCommand::CHECK_FAILED, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Not all requirements are fulfilled, installation not possible.', $display);
        $this->assertStringNotContainsString('There were some warnings, installation may still be possible.', $display);
    }

    public function testExecuteQueriesForTablesMatchingConfiguredDatabasePrefix(): void
    {
        $this->parameterBag
            ->method('get')
            ->willReturnMap([['kernel.project_dir', $this->tempRootDirectory . '/app'], ['app.database_prefix', 'custom_prefix_']]);
        $this->createRequiredDirectories(self::REQUIRED_WRITABLE_DIRECTORIES);

        $result = $this->createStub(Result::class);
        $result->method('rowCount')
            ->willReturn(0);

        // Stays a mock: it has expectations (once, with)
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with(self::stringContains('custom_prefix_%'))
            ->willReturn($result);

        $doctrine = $this->createStub(ManagerRegistry::class);
        $doctrine
            ->method('getConnection')
            ->willReturn($connection);

        $tester = new CommandTester($this->createCommand($doctrine));
        $tester->execute([]);
    }

    public function testEvaluatePhpVersionReturnsOkayWhenRunningVersionMeetsRequirement(): void
    {
        $command = new TestableInstallCheckCommand($this->parameterBag, $this->doctrine);

        $this->assertSame(InstallCheckCommand::OKAY, $command->evaluatePhpVersionPublic(InstallCheckCommand::REQUIRED_PHP_VERSION));
        $this->assertSame(InstallCheckCommand::OKAY, $command->evaluatePhpVersionPublic('8.5.12'));
    }

    public function testEvaluatePhpVersionReturnsErrorWhenRunningVersionIsTooOld(): void
    {
        $command = new TestableInstallCheckCommand($this->parameterBag, $this->doctrine);

        [$requiredMajor] = explode('.', InstallCheckCommand::REQUIRED_PHP_VERSION);
        $belowRequiredMajor = max(0, ((int) $requiredMajor) - 1);
        $versionOneMajorBelowRequired = $belowRequiredMajor . '.0.0';

        $this->assertSame(InstallCheckCommand::ERROR, $command->evaluatePhpVersionPublic($versionOneMajorBelowRequired));
        $this->assertSame(InstallCheckCommand::ERROR, $command->evaluatePhpVersionPublic('0.1.0'));
    }

    public function testCheckPhpVersionUsesTheActualRunningPhpVersion(): void
    {
        $command = new TestableInstallCheckCommand($this->parameterBag, $this->doctrine);

        $this->assertSame(InstallCheckCommand::OKAY, $command->checkPhpVersionPublic());
    }

    public function testExecuteFailsOverallWhenPhpVersionCheckReturnsError(): void
    {
        $this->configureKernelRootDir();
        $this->mockPrefixQuery(0);
        $this->createRequiredDirectories(self::REQUIRED_WRITABLE_DIRECTORIES);

        $command = new TestableInstallCheckCommandForcingPhpVersionError($this->parameterBag, $this->doctrine);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(InstallCheckCommand::CHECK_FAILED, $exitCode);
        $this->assertStringContainsString('Not all requirements are fulfilled, installation not possible.', $tester->getDisplay());
    }
}

final class TestableInstallCheckCommand extends InstallCheckCommand
{
    public function evaluatePhpVersionPublic(string $runningVersion): int
    {
        return $this->evaluatePhpVersion($runningVersion);
    }

    public function checkPhpVersionPublic(): int
    {
        return $this->checkPhpVersion();
    }
}

final class TestableInstallCheckCommandForcingPhpVersionError extends InstallCheckCommand
{
    protected function checkPhpVersion(): int
    {
        return self::ERROR;
    }
}
