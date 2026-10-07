<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Services\Import;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContext;
use Runalyze\Bundle\CoreBundle\Component\Activity\ActivityContextAdapter;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Runalyze\Bundle\CoreBundle\Services\Import\ActivityContextAdapterFactory;
use Runalyze\Bundle\CoreBundle\Services\Import\DuplicateFinder;
use Runalyze\Bundle\CoreBundle\Services\Import\WeatherForecast;
use Runalyze\Service\RouteNameEvaluation\RouteNameEval;
use Runalyze\Service\RouteNameEvaluation\RouteNameEvalFactory;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ActivityContextAdapterFactoryTest extends TestCase
{
    public function testGetAdapterForReturnsAnActivityContextAdapter(): void
    {
        $routeNameEval = $this->createStub(RouteNameEval::class);
        $routeNameEvalFactory = $this->createStub(RouteNameEvalFactory::class);
        $routeNameEvalFactory->method('getInstance')
            ->willReturn($routeNameEval);

        $factory = new ActivityContextAdapterFactory(
            $this->createStub(WeatherForecast::class),
            $this->createStub(DuplicateFinder::class),
            $routeNameEvalFactory
        );

        $context = new ActivityContext($this->createStub(Training::class));
        $adapter = $factory->getAdapterFor($context);

        $this->assertInstanceOf(ActivityContextAdapter::class, $adapter);
    }

    public function testRouteNameEvalIsResolvedOnceAndReusedAcrossMultipleAdapters(): void
    {
        // Stays a mock: it has an expectation (once)
        $routeNameEvalFactory = $this->createMock(RouteNameEvalFactory::class);
        $routeNameEvalFactory->expects($this->once())
            ->method('getInstance')
            ->willReturn($this->createStub(RouteNameEval::class));

        $factory = new ActivityContextAdapterFactory(
            $this->createStub(WeatherForecast::class),
            $this->createStub(DuplicateFinder::class),
            $routeNameEvalFactory
        );

        $context = new ActivityContext($this->createStub(Training::class));
        $factory->getAdapterFor($context);
        $factory->getAdapterFor($context);
    }

    public function testEachCallToGetAdapterForReturnsANewAdapterInstance(): void
    {
        $routeNameEvalFactory = $this->createStub(RouteNameEvalFactory::class);
        $routeNameEvalFactory->method('getInstance')
            ->willReturn($this->createStub(RouteNameEval::class));

        $factory = new ActivityContextAdapterFactory(
            $this->createStub(WeatherForecast::class),
            $this->createStub(DuplicateFinder::class),
            $routeNameEvalFactory
        );

        $context = new ActivityContext($this->createStub(Training::class));

        $this->assertNotSame($factory->getAdapterFor($context), $factory->getAdapterFor($context));
    }
}
