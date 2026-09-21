<?php

namespace Runalyze\Bundle\CoreBundle\EventListener;

use ProxyManager\Proxy\LazyLoadingInterface;

class RecalculationTasksPerformerListener
{
    public function __construct(
        protected \Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager $Manager
    ) {
    }

    public function onTerminate(): void
    {
        if ($this->Manager instanceof LazyLoadingInterface && ! $this->Manager->isProxyInitialized()) {
            return;
        }

        $this->Manager->runScheduledTasks();
    }
}
