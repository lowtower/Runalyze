<?php

namespace Tests\Runalyze\Bundle\CoreBundle\Bridge\Activity\Calculation;

use PHPUnit\Framework\MockObject\MockObject;
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

    private function createTrackdata(bool $hasDistance): Trackdata&MockObject
    {
        $trackData = $this->createMock(Trackdata::class);
        $trackData->method('hasDistance')
            ->willReturn($hasDistance);

        return $trackData;
    }

    private function createRoute(bool $hasElevations): Route&MockObject
    {
        $route = $this->createMock(Route::class);
        $route->method('hasElevations')
            ->willReturn($hasElevations);

        return $route;
    }

    private function createKernel(): AbstractKernel&MockObject
    {
        return $this->createMock(AbstractKernel::class);
    }

    public function testCalculateForCallsSetGradientWithNoArgumentsWhenTrackdataHasNoDistance(): void
    {
        $trackData = $this->createTrackdata(false);
        $route = $this->createRoute(true);

        $trackData->expects(self::once())->method('setGradient')->with(null);

        $route->expects(self::never())->method('getElevations');
        $trackData->expects(self::never())->method('getDistance');

        $this->calculator->calculateFor($trackData, $route, $this->createKernel());
    }

    public function testCalculateForCallsSetGradientWithNoArgumentsWhenRouteHasNoElevations(): void
    {
        $trackData = $this->createTrackdata(true);
        $route = $this->createRoute(false);

        $trackData->expects(self::once())->method('setGradient')->with(null);

        $route->expects(self::never())->method('getElevations');
        $trackData->expects(self::never())->method('getDistance');

        $this->calculator->calculateFor($trackData, $route, $this->createKernel());
    }

    public function testCalculateForCallsSetGradientWithNoArgumentsWhenBothConditionsFail(): void
    {
        $trackData = $this->createTrackdata(false);
        $route = $this->createRoute(false);

        $trackData->expects(self::once())->method('setGradient')->with(null);

        $this->calculator->calculateFor($trackData, $route, $this->createKernel());
    }

    public function testCalculateForComputesGradientSeriesUsingRealGradientAndKernel(): void
    {
        $elevation = [100, 102, 108, 104, 100];
        $distance = [0.0, 0.1, 0.2, 0.3, 0.4];

        $trackData = $this->createTrackdata(true);
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

                return $this->createMock(Trackdata::class);
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

                return $this->createMock(Trackdata::class);
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
