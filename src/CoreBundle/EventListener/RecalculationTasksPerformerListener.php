<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Doctrine\Persistence\Proxy;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;

class RecalculationTasksPerformerListener
{
    public function __construct(
        protected RecalculationManager $Manager
    ) {}

    #[AsEventListener(event: 'kernel.terminate')]
    public function onTerminate(): void
    {
        if ($this->Manager instanceof Proxy && ! $this->Manager->__isInitialized()) {
            return;
        }

        $this->Manager->runScheduledTasks();
    }
}
