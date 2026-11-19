<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Runalyze\Bundle\CoreBundle\EventListener\RecalculationTasksPerformerListener;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;

#[Group('default')]
final class RecalculationTasksPerformerListenerTest extends TestCase
{
    public function testDoesNotInitializeUnusedNativeLazyManager(): void
    {
        $manager = $this->createMock(RecalculationManager::class);
        $manager->expects(self::never())->method('runScheduledTasks');

        $lazyManager = (new ReflectionClass(RecalculationManager::class))
            ->newLazyProxy(static fn (): RecalculationManager => $manager);

        $listener = new RecalculationTasksPerformerListener($lazyManager);
        $listener->onTerminate();

        self::assertTrue((new ReflectionClass($lazyManager))->isUninitializedLazyObject($lazyManager));
    }

    public function testRunsScheduledTasksOnInitializedNativeLazyManager(): void
    {
        $manager = $this->createMock(RecalculationManager::class);
        $manager->expects(self::once())->method('runScheduledTasks');

        $lazyManager = (new ReflectionClass(RecalculationManager::class))
            ->newLazyProxy(static fn (): RecalculationManager => $manager);

        (new ReflectionClass($lazyManager))->initializeLazyObject($lazyManager);

        (new RecalculationTasksPerformerListener($lazyManager))->onTerminate();
    }

    public function testRunsScheduledTasksOnRegularManager(): void
    {
        $manager = $this->createMock(RecalculationManager::class);
        $manager->expects(self::once())->method('runScheduledTasks');

        (new RecalculationTasksPerformerListener($manager))->onTerminate();
    }
}
