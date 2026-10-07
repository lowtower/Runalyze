<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation\GradientCalculator;
use Runalyze\Bundle\CoreBundle\Entity\Route;
use Runalyze\Bundle\CoreBundle\Entity\Trackdata;
use Runalyze\Calculation\Math\MovingAverage\Kernel\AbstractKernel;

final class UniformTestKernel extends AbstractKernel
{
    protected function atTransformed(float $u): float
    {
        return 1.0;
    }
}

final class GradientCalculatorTest extends TestCase
{
    private GradientCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new GradientCalculator();
    }

    private function configureTrackdata(Trackdata $trackData, bool $hasDistance): void
    {
        $trackData->method('hasDistance')
            ->willReturn($hasDistance);
    }

    private function configureRoute(Route $route, bool $hasElevations): void
    {
        $route->method('hasElevations')
            ->willReturn($hasElevations);
    }

    /**
     * For tests that only need return values.
     */
    private function createTrackdata(bool $hasDistance): Trackdata&Stub
    {
        $trackData = $this->createStub(Trackdata::class);
        $this->configureTrackdata($trackData, $hasDistance);

        return $trackData;
    }

    /**
     * For tests that put expects() on the trackdata.
     */
    private function createTrackdataMock(bool $hasDistance): Trackdata&MockObject
    {
        $trackData = $this->createMock(Trackdata::class);
        $this->configureTrackdata($trackData, $hasDistance);

        return $trackData;
    }

    /**
     * For tests that only need return values.
     */
    private function createRoute(bool $hasElevations): Route&Stub
    {
        $route = $this->createStub(Route::class);
        $this->configureRoute($route, $hasElevations);

        return $route;
    }

    /**
     * For tests that put expects() on the route.
     */
    private function createRouteMock(bool $hasElevations): Route&MockObject
    {
        $route = $this->createMock(Route::class);
        $this->configureRoute($route, $hasElevations);

        return $route;
    }

    private function createKernel(): AbstractKernel&Stub
    {
        return $this->createStub(AbstractKernel::class);
    }

    public function testCalculateForCallsSetGradientWithNoArgumentsWhenTrackdataHasNoDistance(): void
    {
        $trackData = $this->createTrackdataMock(false);
        $route = $this->createRouteMock(true);

        $trackData->expects(self::once())->method('setGradient')->with(null);

        $route->expects(self::never())->method('getElevations');
        $trackData->expects(self::never())->method('getDistance');

        $this->calculator->calculateFor($trackData, $route, $this->createKernel());
    }

    public function testCalculateForCallsSetGradientWithNoArgumentsWhenRouteHasNoElevations(): void
    {
        $trackData = $this->createTrackdataMock(true);
        $route = $this->createRouteMock(false);

        $trackData->expects(self::once())->method('setGradient')->with(null);

        $route->expects(self::never())->method('getElevations');
        $trackData->expects(self::never())->method('getDistance');

        $this->calculator->calculateFor($trackData, $route, $this->createKernel());
    }

    public function testCalculateForCallsSetGradientWithNoArgumentsWhenBothConditionsFail(): void
    {
        $trackData = $this->createTrackdataMock(false);
        $route = $this->createRoute(false);

        $trackData->expects(self::once())->method('setGradient')->with(null);

        $this->calculator->calculateFor($trackData, $route, $this->createKernel());
    }

    public function testCalculateForComputesGradientSeriesUsingRealGradientAndKernel(): void
    {
        $elevation = [100, 102, 108, 104, 100];
        $distance = [0.0, 0.1, 0.2, 0.3, 0.4];

        $trackData = $this->createTrackdataMock(true);
        $trackData->method('getDistance')
            ->willReturn($distance);

        $route = $this->createRoute(true);
        $route->method('getElevations')
            ->willReturn($elevation);

        $expectedSeries = [
            2.666666666666657,
            2.666666666666657,
            1.3333333333333428,
            -0.6666666666666716,
            -2.6666666666666705,
        ];

        $capturedSeries = null;
        $trackData
            ->expects(self::once())
            ->method('setGradient')
            ->willReturnCallback(function (?array $series) use (&$capturedSeries): Trackdata {
                $capturedSeries = $series;

                return $this->createStub(Trackdata::class);
            });

        $this->calculator->calculateFor($trackData, $route, new UniformTestKernel(2.0));

        self::assertIsArray($capturedSeries);
        self::assertCount(count($expectedSeries), $capturedSeries);

        foreach ($expectedSeries as $index => $expectedValue) {
            self::assertEqualsWithDelta(
                $expectedValue,
                $capturedSeries[$index],
                0.0001,
                "Gradient value at index {$index} did not match the independently verified expected value."
            );
        }
    }

    public function testCalculateForPassesTheProvidedKernelThroughToGradient(): void
    {
        $elevation = [100, 102, 108, 104, 100];
        $distance = [0.0, 0.1, 0.2, 0.3, 0.4];

        $trackData = $this->createTrackdata(true);
        $trackData->method('getDistance')
            ->willReturn($distance);

        $route = $this->createRoute(true);
        $route->method('getElevations')
            ->willReturn($elevation);

        $capturedSeries = null;
        $trackData
            ->method('setGradient')
            ->willReturnCallback(function (?array $series) use (&$capturedSeries): Trackdata {
                $capturedSeries = $series;

                return $this->createStub(Trackdata::class);
            });

        $this->calculator->calculateFor($trackData, $route, new UniformTestKernel(2.0));
        $seriesWithNarrowKernel = $capturedSeries;

        $capturedSeries = null;
        $this->calculator->calculateFor($trackData, $route, new UniformTestKernel(4.0));
        $seriesWithWideKernel = $capturedSeries;

        self::assertNotEquals(
            $seriesWithNarrowKernel,
            $seriesWithWideKernel,
            'A materially different kernel width must produce a different gradient series, proving the kernel is actually used.'
        );
    }
}
