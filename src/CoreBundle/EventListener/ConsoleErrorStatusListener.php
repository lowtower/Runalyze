<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class ConsoleErrorStatusListener
{
    public function __construct(
        protected LoggerInterface $Logger
    ) {
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $statusCode = $event->getExitCode();
        $command = $event->getCommand();

        if ($statusCode === 0) {
            return;
        }

        if ($statusCode > 255) {
            $statusCode = 255;
            $event->setExitCode($statusCode);
        }

        $this->Logger->warning(sprintf(
            'Command `%s` exited with status code %d',
            $command->getName(),
            $statusCode
        ));
    }
}
