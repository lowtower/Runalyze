<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Command;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\CleanupBackupCommand;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FinderFactoryInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class CleanupBackupCommandTest extends TestCase
{
    private ParameterBagInterface&MockObject $parameterBag;

    private Filesystem $filesystem;

    private FinderFactoryInterface&MockObject $finderFactory;

    private string $tempDataDirectory;

    private string $backupPath;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->filesystem = new Filesystem();
        $this->finderFactory = $this->createMock(FinderFactoryInterface::class);

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

    private function createCommand(): CleanupBackupCommand
    {
        return new CleanupBackupCommand($this->parameterBag, $this->filesystem, $this->finderFactory);
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

        self::assertSame('runalyze:cleanup:backups', $command->getName());
        self::assertSame(
            'Cleanup user backups older than parameter: backup_storage_period',
            $command->getDescription()
        );

        $definition = $command->getDefinition();

        self::assertTrue($definition->hasArgument('days'));
        self::assertFalse($definition->getArgument('days')->isRequired());
    }

    public function testExecuteUsesProvidedDaysArgumentInsteadOfConfiguredPeriod(): void
    {
        $this->parameterBag
            ->expects(self::once())
            ->method('get')
            ->with('app.data_directory')
            ->willReturn($this->tempDataDirectory);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString(
            'Delete all backups older than 30 days',
            $tester->getDisplay()
        );
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

        self::assertSame(0, $exitCode);
        self::assertStringContainsString(
            'Delete all backups older than 15 days',
            $tester->getDisplay()
        );
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

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('0 deleted backups', $tester->getDisplay());
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

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('1 deleted backups', $tester->getDisplay());

        self::assertFileDoesNotExist($oldBackup, 'The old .gz backup should have been deleted.');
        self::assertFileExists($recentBackup, 'The recent .gz backup must not be deleted.');
        self::assertFileExists($oldNonGzFile, 'Non-.gz files must never be touched, regardless of age.');
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

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('2 deleted backups', $tester->getDisplay());

        self::assertFileDoesNotExist($oldBackup1);
        self::assertFileDoesNotExist($oldBackup2);
        self::assertFileExists($recentBackup);
    }

    public function testExecuteRequestsExactlyOneFinderFromTheFactory(): void
    {
        $this->finderFactory = $this->createMock(FinderFactoryInterface::class);
        $this->finderFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn(new Finder());

        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.data_directory', $this->tempDataDirectory],
            ]);

        $tester = new CommandTester($this->createCommand());
        $tester->execute([
            'days' => '30',
        ]);
    }
}
