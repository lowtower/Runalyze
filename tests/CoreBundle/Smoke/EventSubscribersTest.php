<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Smoke;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[\PHPUnit\Framework\Attributes\Group('requiresKernel')]
final class EventSubscribersTest extends KernelTestCase
{
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = self::getContainer()->get('event_dispatcher');
    }

    public function testCount(): void
    {
        $eventSubscriberClasses = [];

        foreach ($this->eventDispatcher->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                /** @var string $listenerClass */
                $listenerClass = $listener[0]::class;

                // skip native ones
                if (
                    str_starts_with($listenerClass, 'Symfony')
                    || str_starts_with($listenerClass, 'Sensio')
                ) {
                    continue;
                }

                $eventSubscriberClasses[] = $listenerClass;
            }
        }

        $this->assertCount(9, $eventSubscriberClasses);
    }

    public function testNarrowCounts(): void
    {
        $this->assertCount(
            10,
            $this->eventDispatcher->getListeners(KernelEvents::RESPONSE)
        );

        $this->assertCount(
            13,
            $this->eventDispatcher->getListeners(KernelEvents::REQUEST)
        );
    }

    public function testControllerBuild(): void
    {
        $controllerCount = 0;

        foreach (self::getContainer()->getServiceIds() as $serviceId) {
            if (! str_ends_with($serviceId, 'Controller')) {
                continue;
            }

            // check only our controllers
            if (! str_starts_with($serviceId, 'Runalyze')) {
                continue;
            }

            // make sure all controllers are still instantiated
            $controller = self::getContainer()->get($serviceId);
            $this->assertInstanceOf(AbstractController::class, $controller);

            ++$controllerCount;
        }

        $this->assertSame(32, $controllerCount);
    }
}
