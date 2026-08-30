<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;

class ConsoleExceptionListener
{
    /**
     * @var LoggerInterface
     */
    protected $Logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->Logger = $logger;
    }

    public function onConsoleError(ConsoleErrorEvent $event)
    {
        $command = $event->getCommand();
        $error = $event->getError();

        $message = sprintf(
            '%s: %s (uncaught exception) at %s line %s while running console command `%s`',
            get_class($error),
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
