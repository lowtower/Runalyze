<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Doctrine\Persistence\Proxy;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;

class RecalculationTasksPerformerListener
{
    public function __construct(
        protected RecalculationManager $Manager
    ) {
    }

    public function onTerminate(): void
    {
        if ($this->Manager instanceof Proxy && ! $this->Manager->__isInitialized()) {
            return;
        }

        $this->Manager->runScheduledTasks();
    }
}
