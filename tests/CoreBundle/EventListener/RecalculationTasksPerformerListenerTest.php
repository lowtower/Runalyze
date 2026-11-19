<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Runalyze\Bundle\CoreBundle\EventListener\RecalculationTasksPerformerListener;
use Runalyze\Bundle\CoreBundle\Repository\RaceresultRepository;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationManager;
use Runalyze\Bundle\CoreBundle\Services\Configuration\ConfigurationUpdater;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationManager;
use Runalyze\Bundle\CoreBundle\Services\Recalculation\RecalculationTaskCollection;

#[Group('default')]
final class RecalculationTasksPerformerListenerTest extends TestCase
{
    public function testDoesNotInitializeUnusedNativeLazyManager(): void
    {
        $manager = $this->createMock(RecalculationManager::class);
        $manager->expects($this->never())->method('runScheduledTasks');

        $lazyManager = new ReflectionClass(RecalculationManager::class)
            ->newLazyProxy(static fn (): RecalculationManager => $manager);

        $listener = new RecalculationTasksPerformerListener($lazyManager);
        $listener->onTerminate();

        $this->assertTrue(new ReflectionClass($lazyManager)->isUninitializedLazyObject($lazyManager));
    }

    public function testRunsScheduledTasksOnInitializedNativeLazyManager(): void
    {
        $manager = new RecalculationManager(
            $this->createStub(ConfigurationManager::class),
            $this->createStub(ConfigurationUpdater::class),
            $this->createStub(TrainingRepository::class),
            $this->createStub(RaceresultRepository::class)
        );

        $tasks = $this->createMock(RecalculationTaskCollection::class);
        $tasks->expects($this->once())->method('runAllTasks');
        $tasks->expects($this->once())->method('clear');

        new ReflectionClass(RecalculationManager::class)->getProperty('Tasks')->setValue($manager, $tasks);

        $lazyManager = new ReflectionClass(RecalculationManager::class)
            ->newLazyProxy(static fn (): RecalculationManager => $manager);

        new ReflectionClass($lazyManager)->initializeLazyObject($lazyManager);

        new RecalculationTasksPerformerListener($lazyManager)->onTerminate();
    }

    public function testRunsScheduledTasksOnRegularManager(): void
    {
        $manager = $this->createMock(RecalculationManager::class);
        $manager->expects($this->once())->method('runScheduledTasks');

        new RecalculationTasksPerformerListener($manager)->onTerminate();
    }
}
