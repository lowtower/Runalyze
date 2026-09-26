<?php

declare(strict_types=1);

namespace Runalyze\Configuration\Category;

use Runalyze\Parameter\Application\ActivityPlotMode;
use Runalyze\Parameter\Application\ActivityPlotPrecision;
use Runalyze\Parameter\Application\ActivityRouteBreak;
use Runalyze\Parameter\Application\ActivityRoutePrecision;
use Runalyze\Parameter\Application\ElevationMethod;
use Runalyze\Parameter\Application\PaceAxisLimit;
use Runalyze\Parameter\Application\PaceAxisType;

#[\PHPUnit\Framework\Attributes\Group('default')]
class ActivityViewTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityView $object;

    protected function setUp(): void
    {
        $this->object = new ActivityView();
    }

    public function testRoutePrecisionReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertInstanceOf(ActivityRoutePrecision::class, $this->object->routePrecision());
        self::assertSame('5', $this->object->routePrecision()->value());
    }

    public function testRouteBreakReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertInstanceOf(ActivityRouteBreak::class, $this->object->routeBreak());
        self::assertSame('15', $this->object->routeBreak()->value());
    }

    public function testMapFirstDefaultsToFalse(): void
    {
        self::assertFalse($this->object->mapFirst());
    }

    public function testMapLayerReturnsDefaultOpenStreetMap(): void
    {
        self::assertSame('OpenStreetMap', $this->object->mapLayer());
    }

    public function testUpdateLayerChangesMapLayer(): void
    {
        $this->object->updateLayer('Satellite');

        self::assertSame('Satellite', $this->object->mapLayer());
    }

    public function testMapZoomOnScrollDefaultsToFalse(): void
    {
        self::assertFalse($this->object->mapZoomOnScroll());
    }

    public function testSmoothCurvesDefaultsToFalse(): void
    {
        self::assertFalse($this->object->smoothCurves());
    }

    public function testUsesTimeAsXAxisDefaultsToFalse(): void
    {
        self::assertFalse($this->object->usesTimeAsXAxis());
    }

    public function testPlotModeReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertInstanceOf(ActivityPlotMode::class, $this->object->plotMode());
        self::assertSame(ActivityPlotMode::SEPERATED, $this->object->plotMode()->value());
    }

    public function testPlotPrecisionReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertInstanceOf(ActivityPlotPrecision::class, $this->object->plotPrecision());
        self::assertSame('200points', $this->object->plotPrecision()->value());
    }

    public function testStartSplitsAtZeroDefaultsToTrue(): void
    {
        self::assertTrue($this->object->startSplitsAtZero());
    }

    public function testPaceYaxisMinimumReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertSame(PaceAxisLimit::AUTO, $this->object->paceYaxisMinimum()->value());
        self::assertTrue($this->object->paceYaxisMinimum()->automatic());
    }

    public function testPaceYaxisMaximumReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertSame(PaceAxisLimit::AUTO, $this->object->paceYaxisMaximum()->value());
        self::assertTrue($this->object->paceYaxisMaximum()->automatic());
    }

    public function testPaceAxisTypeReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertInstanceOf(PaceAxisType::class, $this->object->paceAxisType());
        self::assertSame(PaceAxisType::AS_SPEED, $this->object->paceAxisType()->value());
    }

    public function testIgnorePaceOutliersDefaultsToFalse(): void
    {
        self::assertFalse($this->object->ignorePaceOutliers());
    }

    public function testDecimalsDefaultsToOne(): void
    {
        self::assertSame(1, $this->object->decimals());
    }

    public function testShowSectionsFullheightDefaultsToFalse(): void
    {
        self::assertFalse($this->object->showSectionsFullheight());
    }

    public function testElevationMethodReturnsConfiguredParameterWithDefaultValue(): void
    {
        self::assertInstanceOf(ElevationMethod::class, $this->object->elevationMethod());
        self::assertSame(ElevationMethod::THRESHOLD, $this->object->elevationMethod()->value());
        self::assertTrue($this->object->elevationMethod()->usesThreshold());
    }

    public function testElevationMinDiffDefaultsToThree(): void
    {
        self::assertSame(3, $this->object->elevationMinDiff());
    }
}
