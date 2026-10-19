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
final class ActivityViewTest extends \PHPUnit\Framework\TestCase
{
    protected ActivityView $object;

    protected function setUp(): void
    {
        $this->object = new ActivityView();
    }

    public function testRoutePrecisionReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(ActivityRoutePrecision::class, $this->object->routePrecision());
        $this->assertSame('5', $this->object->routePrecision()->value());
    }

    public function testRouteBreakReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(ActivityRouteBreak::class, $this->object->routeBreak());
        $this->assertSame('15', $this->object->routeBreak()->value());
    }

    public function testMapFirstDefaultsToFalse(): void
    {
        $this->assertFalse($this->object->mapFirst());
    }

    public function testMapLayerReturnsDefaultOpenStreetMap(): void
    {
        $this->assertSame('OpenStreetMap', $this->object->mapLayer());
    }

    public function testUpdateLayerChangesMapLayer(): void
    {
        $this->object->updateLayer('Satellite');

        $this->assertSame('Satellite', $this->object->mapLayer());
    }

    public function testMapZoomOnScrollDefaultsToFalse(): void
    {
        $this->assertFalse($this->object->mapZoomOnScroll());
    }

    public function testSmoothCurvesDefaultsToFalse(): void
    {
        $this->assertFalse($this->object->smoothCurves());
    }

    public function testUsesTimeAsXAxisDefaultsToFalse(): void
    {
        $this->assertFalse($this->object->usesTimeAsXAxis());
    }

    public function testPlotModeReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(ActivityPlotMode::class, $this->object->plotMode());
        $this->assertSame(ActivityPlotMode::SEPERATED, $this->object->plotMode()->value());
    }

    public function testPlotPrecisionReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(ActivityPlotPrecision::class, $this->object->plotPrecision());
        $this->assertSame('200points', $this->object->plotPrecision()->value());
    }

    public function testStartSplitsAtZeroDefaultsToTrue(): void
    {
        $this->assertTrue($this->object->startSplitsAtZero());
    }

    public function testPaceYaxisMinimumReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertSame(PaceAxisLimit::AUTO, $this->object->paceYaxisMinimum()->value());
        $this->assertTrue($this->object->paceYaxisMinimum()->automatic());
    }

    public function testPaceYaxisMaximumReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertSame(PaceAxisLimit::AUTO, $this->object->paceYaxisMaximum()->value());
        $this->assertTrue($this->object->paceYaxisMaximum()->automatic());
    }

    public function testPaceAxisTypeReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(PaceAxisType::class, $this->object->paceAxisType());
        $this->assertSame(PaceAxisType::AS_SPEED, $this->object->paceAxisType()->value());
    }

    public function testIgnorePaceOutliersDefaultsToFalse(): void
    {
        $this->assertFalse($this->object->ignorePaceOutliers());
    }

    public function testDecimalsDefaultsToOne(): void
    {
        $this->assertSame(1, $this->object->decimals());
    }

    public function testShowSectionsFullheightDefaultsToFalse(): void
    {
        $this->assertFalse($this->object->showSectionsFullheight());
    }

    public function testElevationMethodReturnsConfiguredParameterWithDefaultValue(): void
    {
        $this->assertInstanceOf(ElevationMethod::class, $this->object->elevationMethod());
        $this->assertSame(ElevationMethod::THRESHOLD, $this->object->elevationMethod()->value());
        $this->assertTrue($this->object->elevationMethod()->usesThreshold());
    }

    public function testElevationMinDiffDefaultsToThree(): void
    {
        $this->assertSame(3, $this->object->elevationMinDiff());
    }
}
