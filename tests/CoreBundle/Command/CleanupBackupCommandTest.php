<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\CleanupBackupCommand;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FinderFactoryInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class CleanupBackupCommandTest extends TestCase
{
    private ParameterBagInterface&Stub $parameterBag;

    private Filesystem $filesystem;

    private FinderFactoryInterface&Stub $finderFactory;

    private string $tempDataDirectory;

    private string $backupPath;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createStub(ParameterBagInterface::class);
        $this->filesystem = new Filesystem();
        $this->finderFactory = $this->createStub(FinderFactoryInterface::class);

        $this->finderFactory
            ->method('create')
            ->willReturnCallback(static fn (): Finder => new Finder());

        $this->tempDataDirectory = sys_get_temp_dir() . '/cleanup-backup-command-test-' . bin2hex(random_bytes(8));
        $this->backupPath = $this->tempDataDirectory . '/backup-tool/backup';

        mkdir($this->backupPath, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDataDirectory)) {
            $this->filesystem->remove($this->tempDataDirectory);
        }
    }

    private function createCommand(
        ?ParameterBagInterface $parameterBag = null,
        ?FinderFactoryInterface $finderFactory = null
    ): CleanupBackupCommand {
        return new CleanupBackupCommand(
            $parameterBag ?? $this->parameterBag,
            $this->filesystem,
            $finderFactory ?? $this->finderFactory
        );
    }

    /**
     * @param int $ageInDays how many days ago the file's mtime should be set to
     */
    private function createBackupFile(string $filename, int $ageInDays): string
    {
        $path = $this->backupPath . '/' . $filename;
        file_put_contents($path, 'dummy backup content');
        touch($path, time() - ($ageInDays * 86400));

        return $path;
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->createCommand();

        $this->assertSame('runalyze:cleanup:backups', $command->getName());
        $this->assertSame('Cleanup user backups older than parameter: backup_storage_period', $command->getDescription());

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('days'));
        $this->assertFalse($definition->getArgument('days')->isRequired());
    }

    public function testExecuteUsesProvidedDaysArgumentInsteadOfConfiguredPeriod(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag
            ->expects($this->once())
            ->method('get')
            ->with('app.data_directory')
            ->willReturn($this->tempDataDirectory);

        $tester = new CommandTester($this->createCommand($parameterBag));
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Delete all backups older than 30 days', $tester->getDisplay());
    }

    public function testExecuteFallsBackToConfiguredBackupStoragePeriodWhenDaysArgumentOmitted(): void
    {
        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.data_directory', $this->tempDataDirectory],
                ['app.backup_storage_period', 15],
            ]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Delete all backups older than 15 days', $tester->getDisplay());
    }

    public function testExecuteReportsZeroDeletedBackupsForEmptyDirectory(): void
    {
        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.data_directory', $this->tempDataDirectory],
            ]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('0 deleted backups', $tester->getDisplay());
    }

    public function testExecuteDeletesOnlyGzFilesOlderThanGivenDays(): void
    {
        $oldBackup = $this->createBackupFile('old-backup.gz', 40);
        $recentBackup = $this->createBackupFile('recent-backup.gz', 5);
        $oldNonGzFile = $this->createBackupFile('old-notes.txt', 40);

        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.data_directory', $this->tempDataDirectory],
            ]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('1 deleted backups', $tester->getDisplay());

        $this->assertFileDoesNotExist($oldBackup, 'The old .gz backup should have been deleted.');
        $this->assertFileExists($recentBackup, 'The recent .gz backup must not be deleted.');
        $this->assertFileExists($oldNonGzFile, 'Non-.gz files must never be touched, regardless of age.');
    }

    public function testExecuteDeletesMultipleMatchingBackupsAndReportsCorrectCount(): void
    {
        $oldBackup1 = $this->createBackupFile('old-backup-1.gz', 45);
        $oldBackup2 = $this->createBackupFile('old-backup-2.gz', 60);
        $recentBackup = $this->createBackupFile('recent-backup.gz', 1);

        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.data_directory', $this->tempDataDirectory],
            ]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('2 deleted backups', $tester->getDisplay());

        $this->assertFileDoesNotExist($oldBackup1);
        $this->assertFileDoesNotExist($oldBackup2);
        $this->assertFileExists($recentBackup);
    }

    public function testExecuteRequestsExactlyOneFinderFromTheFactory(): void
    {
        $finderFactory = $this->createMock(FinderFactoryInterface::class);
        $finderFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new Finder());

        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.data_directory', $this->tempDataDirectory],
            ]);

        $tester = new CommandTester($this->createCommand(null, $finderFactory));
        $tester->execute([
            'days' => '30',
        ]);
    }
}
