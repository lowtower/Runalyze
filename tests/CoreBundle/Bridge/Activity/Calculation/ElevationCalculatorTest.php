<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Bridge\Activity\Calculation;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\ElevationCalculator;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Parameter\Application\ElevationMethod;

final class ElevationCalculatorTest extends TestCase
{
    private ElevationCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ElevationCalculator();
    }

    private function createRoute(bool $hasElevations, ?array $elevations = null): Route&MockObject
    {
        $route = $this->createMock(Route::class);
        $route->method('hasElevations')
            ->willReturn($hasElevations);

        if ($elevations !== null) {
            $route->method('getElevations')
                ->willReturn($elevations);
        }

        return $route;
    }

    private function createNoSmoothingMethod(): ElevationMethod
    {
        $method = new ElevationMethod();
        $method->set(ElevationMethod::NONE);

        return $method;
    }

    public function testCalculateForSetsAllElevationValuesToZeroWhenRouteHasNoElevations(): void
    {
        $route = $this->createRoute(false);

        $route->expects($this->once())->method('setElevation')->with(0)->willReturn($route);
        $route->expects($this->once())->method('setElevationUp')->with(0)->willReturn($route);
        $route->expects($this->once())->method('setElevationDown')->with(0)->willReturn($route);

        $route->expects($this->never())->method('getElevations');

        $this->calculator->calculateFor($route, new ElevationMethod(), 5);
    }

    public function testCalculateForDoesNotThrowWhenRouteHasNoElevationsRegardlessOfMethodOrThreshold(): void
    {
        $route = $this->createRoute(false);

        $route->expects($this->once())->method('setElevation')->with(0)->willReturn($route);
        $route->expects($this->once())->method('setElevationUp')->with(0)->willReturn($route);
        $route->expects($this->once())->method('setElevationDown')->with(0)->willReturn($route);

        $method = new ElevationMethod();
        $method->set(ElevationMethod::DOUGLAS_PEUCKER);

        $this->calculator->calculateFor($route, $method, 100);
    }

    public function testCalculateForComputesAndSetsElevationValuesUsingNoSmoothing(): void
    {
        $elevations = [100, 105, 102, 110, 108];
        $route = $this->createRoute(true, $elevations);

        $route->expects($this->once())->method('setElevation')->with(13)->willReturn($route);
        $route->expects($this->once())->method('setElevationUp')->with(13)->willReturn($route);
        $route->expects($this->once())->method('setElevationDown')->with(5)->willReturn($route);

        $this->calculator->calculateFor($route, $this->createNoSmoothingMethod(), 0);
    }

    public function testCalculateForReturnsZeroValuesWhenElevationsArrayIsEmptyEvenThoughHasElevationsIsTrue(): void
    {
        $route = $this->createRoute(true, []);

        $route->expects($this->once())->method('setElevation')->with(0)->willReturn($route);
        $route->expects($this->once())->method('setElevationUp')->with(0)->willReturn($route);
        $route->expects($this->once())->method('setElevationDown')->with(0)->willReturn($route);

        $this->calculator->calculateFor($route, $this->createNoSmoothingMethod(), 0);
    }

    public function testCalculateForHandlesAMonotonicallyAscendingRouteWithNoDescent(): void
    {
        $elevations = [0, 10, 20, 30];
        $route = $this->createRoute(true, $elevations);

        $route->expects($this->once())->method('setElevation')->with(30)->willReturn($route);
        $route->expects($this->once())->method('setElevationUp')->with(30)->willReturn($route);
        $route->expects($this->once())->method('setElevationDown')->with(0)->willReturn($route);

        $this->calculator->calculateFor($route, $this->createNoSmoothingMethod(), 0);
    }
}
