<?php

declare(strict_types=1);

namespace Runalyze\View\Window\Laps;

use PHPUnit\Framework\TestCase;
use Runalyze\Activity\Duration;
use Runalyze\Data\Laps\Laps;
use Runalyze\Metrics\Velocity\Unit\AbstractPaceUnit;
use Runalyze\Metrics\Velocity\Unit\KilometerPerHour;
use Runalyze\Metrics\Velocity\Unit\MeterPerSecond;
use Runalyze\Metrics\Velocity\Unit\SecondsPerKilometer;
use Runalyze\Profile\Sport\SportProfile;

/**
 * Tests for the "Pace"/"Speed" column header in Runalyze\View\Window\Laps\Table.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Runalyze\View\Window\Laps\Table::class)]
final class TableHeaderTest extends TestCase
{
    /**
     * Creates a Table instance with a given pace unit, without
     * requiring a fully populated Laps object.
     */
    private function createTable(AbstractPaceUnit $paceUnit, int $sportProfileEnum = SportProfile::RUNNING): Table
    {
        $laps = $this->createStub(Laps::class);
        $laps->method('hasMoreThanActiveOrRestLaps')
            ->willReturn(false);
        $laps->method('objects')
            ->willReturn([]);
        $laps->method('num')
            ->willReturn(0);

        return new Table(
            $laps,
            new Duration(0),
            0.0,
            $paceUnit,
            $sportProfileEnum
        );
    }

    /**
     * Invokes the protected tableHeader() method via reflection.
     */
    private function renderHeader(Table $table): string
    {
        $method = new \ReflectionMethod(Table::class, 'tableHeader');

        return $method->invoke($table);
    }

    public function testHeaderShowsPaceLabelForTimeFormatUnit(): void
    {
        $header = $this->renderHeader($this->createTable(new SecondsPerKilometer()));

        $this->assertStringContainsString('>' . __('Pace') . '<', $header);
        $this->assertStringNotContainsString('>' . __('Speed') . '<', $header);
    }

    public function testHeaderShowsSpeedLabelForKmPerHourUnit(): void
    {
        $header = $this->renderHeader(
            $this->createTable(new KilometerPerHour(), SportProfile::CYCLING)
        );

        $this->assertStringContainsString('>' . __('Speed') . '<', $header);
        $this->assertStringNotContainsString('>' . __('Pace') . '<', $header);
    }

    public function testHeaderShowsSpeedLabelForMeterPerSecondUnit(): void
    {
        $header = $this->renderHeader(
            $this->createTable(new MeterPerSecond(), SportProfile::SWIMMING)
        );

        $this->assertStringContainsString('>' . __('Speed') . '<', $header);
        $this->assertStringNotContainsString('>' . __('Pace') . '<', $header);
    }

    public function testHeaderDoesNotShowPaceForBikingActivityWithKmPerHour(): void
    {
        $table = $this->createTable(new KilometerPerHour(), SportProfile::CYCLING);
        $header = $this->renderHeader($table);

        $this->assertStringNotContainsString(
            '>' . __('Pace') . '<',
            $header,
            'Header must not display "Pace" for a speed-based unit (km/h) such as cycling activities.'
        );
    }

    public function testHeaderLabelIsIndependentOfEmptyPaceValue(): void
    {
        $header = $this->renderHeader($this->createTable(new KilometerPerHour(), SportProfile::CYCLING));

        $this->assertStringContainsString('>' . __('Speed') . '<', $header);
    }
}
