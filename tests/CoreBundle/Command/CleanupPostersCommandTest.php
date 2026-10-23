<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\CleanupPostersCommand;
use Runalyze\Bundle\CoreBundle\Component\Tool\Backup\FinderFactoryInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

final class CleanupPostersCommandTest extends TestCase
{
    private ParameterBagInterface&Stub $parameterBag;

    private Filesystem $filesystem;

    private FinderFactoryInterface&Stub $finderFactory;

    private string $tempDataDirectory;

    private string $posterPath;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createStub(ParameterBagInterface::class);
        $this->filesystem = new Filesystem();
        $this->finderFactory = $this->createStub(FinderFactoryInterface::class);

        $this->finderFactory
            ->method('create')
            ->willReturnCallback(static fn (): Finder => new Finder());

        $this->tempDataDirectory = sys_get_temp_dir() . '/cleanup-posters-command-test-' . bin2hex(random_bytes(8));
        $this->posterPath = $this->tempDataDirectory . '/poster/';

        mkdir($this->posterPath, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDataDirectory)) {
            $this->filesystem->remove($this->tempDataDirectory);
        }
    }

    private function createCommand(?FinderFactoryInterface $finderFactory = null): CleanupPostersCommand
    {
        return new CleanupPostersCommand($this->parameterBag, $this->filesystem, $finderFactory ?? $this->finderFactory);
    }

    /**
     * @param int $ageInDays how many days ago the file's mtime should be set to
     */
    private function createPosterFile(string $filename, int $ageInDays): string
    {
        $path = $this->posterPath . $filename;
        file_put_contents($path, 'dummy poster content');
        touch($path, time() - ($ageInDays * 86400));

        return $path;
    }

    private function configureDataDirectory(): void
    {
        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.data_directory', $this->tempDataDirectory],
            ]);
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->createCommand();

        $this->assertSame('runalyze:cleanup:posters', $command->getName());
        $this->assertSame('Cleanup posters older than parameter: poster_storage_period', $command->getDescription());

        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('days'));
        $this->assertFalse($definition->getArgument('days')->isRequired());
    }

    public function testExecuteUsesProvidedDaysArgumentInsteadOfConfiguredPeriod(): void
    {
        $this->configureDataDirectory();

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Delete all posters older than 30 days', $tester->getDisplay());
    }

    public function testExecuteFallsBackToConfiguredPosterStoragePeriodWhenDaysArgumentOmitted(): void
    {
        $this->parameterBag
            ->method('get')
            ->willReturnMap([['app.data_directory', $this->tempDataDirectory], ['app.poster_storage_period', 20]]);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Delete all posters older than 20 days', $tester->getDisplay());
    }

    public function testExecuteReportsZeroDeletedPostersForEmptyDirectory(): void
    {
        $this->configureDataDirectory();

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('0 deleted posters', $tester->getDisplay());
    }

    public function testExecuteDeletesOnlyPngFilesOlderThanGivenDays(): void
    {
        $oldPoster = $this->createPosterFile('old-poster.png', 40);
        $recentPoster = $this->createPosterFile('recent-poster.png', 5);
        $oldNonPngFile = $this->createPosterFile('old-notes.txt', 40);

        $this->configureDataDirectory();

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('1 deleted posters', $tester->getDisplay());

        $this->assertFileDoesNotExist($oldPoster, 'The old .png poster should have been deleted.');
        $this->assertFileExists($recentPoster, 'The recent .png poster must not be deleted.');
        $this->assertFileExists($oldNonPngFile, 'Non-.png files must never be touched, regardless of age.');
    }

    public function testExecuteDeletesMultipleMatchingPostersAndReportsCorrectCount(): void
    {
        $oldPoster1 = $this->createPosterFile('old-poster-1.png', 45);
        $oldPoster2 = $this->createPosterFile('old-poster-2.png', 60);
        $recentPoster = $this->createPosterFile('recent-poster.png', 1);

        $this->configureDataDirectory();

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([
            'days' => '30',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('2 deleted posters', $tester->getDisplay());

        $this->assertFileDoesNotExist($oldPoster1);
        $this->assertFileDoesNotExist($oldPoster2);
        $this->assertFileExists($recentPoster);
    }

    public function testExecuteRequestsExactlyOneFinderFromTheFactory(): void
    {
        $finderFactory = $this->createMock(FinderFactoryInterface::class);
        $finderFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn(new Finder());

        $this->configureDataDirectory();

        $tester = new CommandTester($this->createCommand($finderFactory));
        $tester->execute([
            'days' => '30',
        ]);
    }
}
