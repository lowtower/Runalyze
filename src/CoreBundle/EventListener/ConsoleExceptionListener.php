<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;

class ConsoleExceptionListener
{
    public function __construct(
        protected LoggerInterface $Logger
    ) {
    }

    public function onConsoleError(ConsoleErrorEvent $event): void
    {
        $command = $event->getCommand();
        $error = $event->getError();

        $message = sprintf(
            '%s: %s (uncaught exception) at %s line %s while running console command `%s`',
            $error::class,
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
            $command !== null ? $command->getName() : 'n/a'
        );

        $this->Logger->error($message, [
            'exception' => $error,
        ]);
    }
}
