<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\EventListener;

use Doctrine\Persistence\Proxy;
use ReflectionClass;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\VarExporter\LazyObjectInterface;

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

        if ($this->Manager instanceof LazyObjectInterface && ! $this->Manager->isLazyObjectInitialized()) {
            return;
        }

        if (new ReflectionClass($this->Manager)->isUninitializedLazyObject($this->Manager)) {
            return;
        }

        $this->Manager->runScheduledTasks();
    }
}
