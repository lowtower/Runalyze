<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
final class ActivityPlotModeTest extends TestCase
{
    private ActivityPlotMode $mode;

    protected function setUp(): void
    {
        $this->mode = new ActivityPlotMode();
    }

    public function testDefaultModeIsSeperated(): void
    {
        $this->assertSame(ActivityPlotMode::SEPERATED, $this->mode->value());
        $this->assertTrue($this->mode->showSeperated());
        $this->assertFalse($this->mode->showPaceAndHR());
        $this->assertFalse($this->mode->showCollection());
    }

    public function testPaceAndHeartRateMode(): void
    {
        $this->mode->set(ActivityPlotMode::PACE_HR);

        $this->assertSame(ActivityPlotMode::PACE_HR, $this->mode->value());
        $this->assertFalse($this->mode->showSeperated());
        $this->assertTrue($this->mode->showPaceAndHR());
        $this->assertFalse($this->mode->showCollection());
    }

    public function testPaceHeartRateAndElevationMode(): void
    {
        $this->mode->set(ActivityPlotMode::PACE_HR_ELEVATION);

        $this->assertSame(ActivityPlotMode::PACE_HR_ELEVATION, $this->mode->value());
        $this->assertFalse($this->mode->showSeperated());
        $this->assertFalse($this->mode->showPaceAndHR());
        $this->assertTrue($this->mode->showCollection());
    }

    public function testUnknownModeFallsBackToDefault(): void
    {
        $this->mode->set(ActivityPlotMode::PACE_HR);
        $this->mode->set('not-a-real-mode');

        $this->assertSame(ActivityPlotMode::SEPERATED, $this->mode->value());
    }
}
