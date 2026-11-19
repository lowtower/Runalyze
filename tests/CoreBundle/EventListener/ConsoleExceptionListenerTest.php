<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Runalyze\Bundle\CoreBundle\EventListener\ConsoleExceptionListener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

#[Group('default')]
final class ConsoleExceptionListenerTest extends TestCase
{
    public function testLogsExceptionWithCommandName(): void
    {
        $exception = new RuntimeException('Something failed');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            sprintf(
                '%s: %s (uncaught exception) at %s line %s while running console command `%s`',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                'app:example'
            ),
            ['exception' => $exception]
        );

        $event = new ConsoleErrorEvent(
            new ArrayInput([]),
            new BufferedOutput(),
            $exception,
            new Command('app:example')
        );

        new ConsoleExceptionListener($logger)->onConsoleError($event);
    }

    public function testLogsExceptionWithoutCommand(): void
    {
        $exception = new RuntimeException('Before command execution');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with(
            sprintf(
                '%s: %s (uncaught exception) at %s line %s while running console command `%s`',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                'n/a'
            ),
            ['exception' => $exception]
        );

        $event = new ConsoleErrorEvent(
            new ArrayInput([]),
            new BufferedOutput(),
            $exception
        );

        new ConsoleExceptionListener($logger)->onConsoleError($event);
    }
}
