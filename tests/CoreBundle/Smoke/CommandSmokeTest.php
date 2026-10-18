<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
final class CommandSmokeTest extends KernelTestCase
{
    private string $commandDir = __DIR__ . '/../../../src/CoreBundle/Command/';

    private string $fullClassNamespace = 'Runalyze\\Bundle\\CoreBundle\\Command\\';

    public function testAllCommandsInstantiateWithoutError(): void
    {
        $commandClasses = [];

        if (is_dir($this->commandDir)) {
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->commandDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            ) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $class = $file->getBasename('.php');
                $fullClass = $this->fullClassNamespace . $class;

                if (class_exists($fullClass)) {
                    $reflection = new \ReflectionClass($fullClass);
                    if ($reflection->isSubclassOf(Command::class)) {
                        $commandClasses[] = $fullClass;
                    }
                }
            }
        }

        if ($commandClasses === [] && self::getContainer()->has('console.command_collection')) {
            $application = new Application(self::getContainer()->get('kernel'));
            $application->setAutoExit(false);

            foreach ($application->all() as $name => $command) {
                if (str_contains($name, 'runalyze')) {
                    $commandClasses[] = $command::class;
                }
            }
        }

        $this->assertGreaterThan(
            0,
            count($commandClasses),
            'No commands found in container or src/'
        );

        foreach ($commandClasses as $commandClass) {
            $command = self::getContainer()->get($commandClass);
            $this->assertInstanceOf(
                Command::class,
                $command,
                "Service {$commandClass} is not a valid Command"
            );
        }
    }

    public function testCommandsDoNotThrowOnConfigure(): void
    {
        $commandClasses = [];

        if (is_dir($this->commandDir)) {
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->commandDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            ) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $class = $file->getBasename('.php');
                $fullClass = $this->fullClassNamespace . $class;

                if (class_exists($fullClass)) {
                    $reflection = new \ReflectionClass($fullClass);
                    if ($reflection->isSubclassOf(Command::class)) {
                        $commandClasses[] = $fullClass;
                    }
                }
            }
        }

        foreach ($commandClasses as $commandClass) {
            $command = self::getContainer()->get($commandClass);

            $this->assertNotNull(
                $command->getName(),
                "Command {$commandClass} has no name after configure()"
            );
        }
    }
}
