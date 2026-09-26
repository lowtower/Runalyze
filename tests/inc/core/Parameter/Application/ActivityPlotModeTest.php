<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('default')]
class ActivityPlotModeTest extends TestCase
{
    private ActivityPlotMode $mode;

    protected function setUp(): void
    {
        $this->mode = new ActivityPlotMode();
    }

    public function testDefaultModeIsSeperated(): void
    {
        self::assertSame(ActivityPlotMode::SEPERATED, $this->mode->value());
        self::assertTrue($this->mode->showSeperated());
        self::assertFalse($this->mode->showPaceAndHR());
        self::assertFalse($this->mode->showCollection());
    }

    public function testPaceAndHeartRateMode(): void
    {
        $this->mode->set(ActivityPlotMode::PACE_HR);

        self::assertSame(ActivityPlotMode::PACE_HR, $this->mode->value());
        self::assertFalse($this->mode->showSeperated());
        self::assertTrue($this->mode->showPaceAndHR());
        self::assertFalse($this->mode->showCollection());
    }

    public function testPaceHeartRateAndElevationMode(): void
    {
        $this->mode->set(ActivityPlotMode::PACE_HR_ELEVATION);

        self::assertSame(ActivityPlotMode::PACE_HR_ELEVATION, $this->mode->value());
        self::assertFalse($this->mode->showSeperated());
        self::assertFalse($this->mode->showPaceAndHR());
        self::assertTrue($this->mode->showCollection());
    }

    public function testUnknownModeFallsBackToDefault(): void
    {
        $this->mode->set(ActivityPlotMode::PACE_HR);
        $this->mode->set('not-a-real-mode');

        self::assertSame(ActivityPlotMode::SEPERATED, $this->mode->value());
    }
}
