<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Command;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Command\InstallCommand;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Throwable;

final class InstallCommandTest extends TestCase
{
    private InvocationRecorder $recorder;

    private string|false $previousShellVerbosity = false;

    protected function setUp(): void
    {
        $this->recorder = new InvocationRecorder();
    }

    protected function tearDown(): void
    {
        if ($this->previousShellVerbosity !== false) {
            putenv('SHELL_VERBOSITY=' . $this->previousShellVerbosity);
            $this->previousShellVerbosity = false;
        }

        parent::tearDown();
    }

    private function poisonShellVerbosityAsQuiet(): void
    {
        $this->previousShellVerbosity = getenv('SHELL_VERBOSITY');
        putenv('SHELL_VERBOSITY=-1');
    }

    /**
     * @param Command[] $stubCommands
     */
    private function createInstallCommandTester(array $stubCommands): CommandTester
    {
        $application = new Application();
        $application->addCommand(new InstallCommand());

        foreach ($stubCommands as $stubCommand) {
            $application->addCommand($stubCommand);
        }

        $installCommand = $application->find('runalyze:install');

        return new CommandTester($installCommand);
    }

    public function testCommandConfiguration(): void
    {
        $command = new InstallCommand();

        self::assertSame('runalyze:install', $command->getName());
        self::assertSame('Install RUNALYZE and setup database.', $command->getDescription());

        $definition = $command->getDefinition();
        self::assertTrue($definition->hasOption('skip'));
        self::assertSame([''], $definition->getOption('skip')->getDefault());
    }

    public function testExecuteRunsAllThreeStepsInOrderAndSucceeds(): void
    {
        $tester = $this->createInstallCommandTester([
            new RecordingStubCommand('runalyze:install:check', $this->recorder),
            new RecordingStubCommand('runalyze:install:database', $this->recorder),
            new RecordingStubCommand('runalyze:install:filesystem', $this->recorder),
        ]);

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertSame(
            ['runalyze:install:check', 'runalyze:install:database', 'runalyze:install:filesystem'],
            $this->recorder->invocations
        );

        $display = $tester->getDisplay();
        self::assertStringContainsString('Installing RUNALYZE...', $display);
        self::assertStringContainsString('Step 1 of 3.', $display);
        self::assertStringContainsString('Check requirements', $display);
        self::assertStringContainsString('Step 2 of 3.', $display);
        self::assertStringContainsString('Setting up the database', $display);
        self::assertStringContainsString('Step 3 of 3.', $display);
        self::assertStringContainsString('Setting up the file system', $display);
        self::assertStringContainsString('RUNALYZE has been successfully installed.', $display);
    }

    public function testExecuteSucceedsEvenWhenShellVerbosityWasLeftQuietByAnEarlierCommand(): void
    {
        $this->poisonShellVerbosityAsQuiet();

        $tester = $this->createInstallCommandTester([
            new RecordingStubCommand('runalyze:install:check', $this->recorder),
            new RecordingStubCommand('runalyze:install:database', $this->recorder),
            new RecordingStubCommand('runalyze:install:filesystem', $this->recorder),
        ]);

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertSame(
            ['runalyze:install:check', 'runalyze:install:database', 'runalyze:install:filesystem'],
            $this->recorder->invocations
        );

        $display = $tester->getDisplay();
        self::assertStringContainsString('Step 2 of 3.', $display);
        self::assertStringContainsString('Step 3 of 3.', $display);
        self::assertStringContainsString('RUNALYZE has been successfully installed.', $display);
    }

    public function testExecuteStopsAtFailingStepAndReturnsItsExitCode(): void
    {
        $tester = $this->createInstallCommandTester([
            new RecordingStubCommand('runalyze:install:check', $this->recorder),
            new RecordingStubCommand('runalyze:install:database', $this->recorder, exitCode: 3),
            new RecordingStubCommand('runalyze:install:filesystem', $this->recorder),
        ]);

        $exitCode = $tester->execute([]);

        self::assertSame(3, $exitCode);
        self::assertSame(
            ['runalyze:install:check', 'runalyze:install:database'],
            $this->recorder->invocations,
            'The filesystem step must not run after the database step failed.'
        );
        self::assertStringNotContainsString(
            'RUNALYZE has been successfully installed.',
            $tester->getDisplay()
        );
    }

    public function testExecuteSkipsStepsListedInSkipOption(): void
    {
        $tester = $this->createInstallCommandTester([
            new RecordingStubCommand('runalyze:install:check', $this->recorder),
            new RecordingStubCommand('runalyze:install:database', $this->recorder),
            new RecordingStubCommand('runalyze:install:filesystem', $this->recorder),
        ]);

        $exitCode = $tester->execute([
            '--skip' => ['database'],
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame(
            ['runalyze:install:check', 'runalyze:install:filesystem'],
            $this->recorder->invocations,
            'The database step must be skipped, but check and filesystem must still run.'
        );
        self::assertStringContainsString('skipped', $tester->getDisplay());
        self::assertStringContainsString('RUNALYZE has been successfully installed.', $tester->getDisplay());
    }

    public function testExecutePropagatesExceptionsFromSubcommandsInsteadOfCatchingThem(): void
    {
        $tester = $this->createInstallCommandTester([
            new RecordingStubCommand(
                'runalyze:install:check',
                $this->recorder,
                exceptionToThrow: new RuntimeException('boom')
            ),
            new RecordingStubCommand('runalyze:install:database', $this->recorder),
            new RecordingStubCommand('runalyze:install:filesystem', $this->recorder),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('boom');

        $tester->execute([]);
    }
}

final class InvocationRecorder
{
    /**
     * @var string[]
     */
    public array $invocations = [];
}

final class RecordingStubCommand extends Command
{
    public function __construct(
        string $name,
        private readonly InvocationRecorder $recorder,
        private readonly int $exitCode = 0,
        private readonly ?Throwable $exceptionToThrow = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->recorder->invocations[] = $this->getName();

        if ($this->exceptionToThrow instanceof Throwable) {
            throw $this->exceptionToThrow;
        }

        return $this->exitCode;
    }
}
