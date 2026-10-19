<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Command;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\InstallFilesystemCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

final class InstallFilesystemCommandTest extends TestCase
{
    private Filesystem $filesystem;

    private KernelInterface&Stub $kernel;

    private string $tempProjectDirectory;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();

        $this->tempProjectDirectory = sys_get_temp_dir() . '/install-filesystem-command-test-' . bin2hex(random_bytes(8));
        mkdir($this->tempProjectDirectory, 0777, true);

        $this->kernel = $this->createStub(\Symfony\Component\HttpKernel\Kernel::class);
        $this->kernel
            ->method('getProjectDir')
            ->willReturn($this->tempProjectDirectory);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempProjectDirectory)) {
            $this->filesystem->remove($this->tempProjectDirectory);
        }
    }

    private function createCommand(): InstallFilesystemCommand
    {
        return new InstallFilesystemCommand($this->filesystem, $this->kernel);
    }

    public function testCommandConfiguration(): void
    {
        $command = $this->createCommand();

        $this->assertSame('runalyze:install:filesystem', $command->getName());
        $this->assertSame('Setup RUNALYZE file system.', $command->getDescription());
    }

    public function testExecuteCopiesHtaccessDistToHtaccessSuccessfully(): void
    {
        $distPath = $this->tempProjectDirectory . '/.htaccess.dist';
        $targetPath = $this->tempProjectDirectory . '/.htaccess';

        file_put_contents($distPath, "Options -Indexes\nRewriteEngine On\n");

        $this->assertFileDoesNotExist($targetPath);

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);

        $this->assertFileExists($targetPath, '.htaccess must be created from .htaccess.dist.');
        $this->assertSame(file_get_contents($distPath), file_get_contents($targetPath), 'The copied .htaccess must have identical content to .htaccess.dist.');

        $this->assertFileExists($distPath);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Setup RUNALYZE file system...', $display);
        $this->assertStringContainsString('Copying .htaccess.dist to .htaccess', $display);
        $this->assertStringContainsString('... done.', $display);
    }

    public function testExecuteCatchesIOExceptionAndStillReturnsSuccessWhenSourceFileIsMissing(): void
    {
        $targetPath = $this->tempProjectDirectory . '/.htaccess';

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);

        $this->assertFileDoesNotExist($targetPath);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Setup RUNALYZE file system...', $display);
        $this->assertStringContainsString('Copying .htaccess.dist to .htaccess', $display);
        $this->assertStringContainsString('... done.', $display);
    }

    public function testExecuteDoesNotOverwriteAnAlreadyNewerExistingHtaccess(): void
    {
        $distPath = $this->tempProjectDirectory . '/.htaccess.dist';
        $targetPath = $this->tempProjectDirectory . '/.htaccess';

        file_put_contents($distPath, 'dist content');
        touch($distPath, time() - 100);

        file_put_contents($targetPath, 'pre-existing content');
        touch($targetPath, time());

        $tester = new CommandTester($this->createCommand());
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertSame('pre-existing content', file_get_contents($targetPath), 'An existing .htaccess that is newer than .htaccess.dist must not be overwritten.');
    }
}
