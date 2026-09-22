<?php

declare(strict_types=1);

namespace Runalyze\View\Window\Laps;

use PHPUnit\Framework\TestCase;
use Runalyze\Activity\Duration;
use Runalyze\Activity\Pace;
use Runalyze\Activity\PaceUnit\KmPerHour;
use Runalyze\Activity\PaceUnit\MeterPerSecond;
use Runalyze\Activity\PaceUnit\MinPerKilometer;
use Runalyze\Data\Laps\Laps;
use Runalyze\Profile\Sport\SportProfile;

/**
 * Tests for the "Pace"/"Speed" column header in Runalyze\View\Window\Laps\Table.
 *
 * @covers \Runalyze\View\Window\Laps\Table
 */
class TableHeaderTest extends TestCase
{
    /**
     * Creates a Table instance with a given DemandedPace unit, without
     * requiring a fully populated Laps object.
     */
    private function createTable(Pace $demandedPace, int $sportProfileEnum = SportProfile::RUNNING): Table
    {
        $laps = $this->createMock(Laps::class);
        $laps->method('hasMoreThanActiveOrRestLaps')
            ->willReturn(false);
        $laps->method('objects')
            ->willReturn([]);
        $laps->method('num')
            ->willReturn(0);

        return new Table(
            $laps,
            new Duration(0),
            $demandedPace,
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
        $pace = new Pace(0, 1);
        $pace->setUnit(new MinPerKilometer());

        $header = $this->renderHeader($this->createTable($pace));

        $this->assertStringContainsString('>' . __('Pace') . '<', $header);
        $this->assertStringNotContainsString('>' . __('Speed') . '<', $header);
    }

    public function testHeaderShowsSpeedLabelForKmPerHourUnit(): void
    {
        $pace = new Pace(0, 1);
        $pace->setUnit(new KmPerHour());

        $header = $this->renderHeader(
            $this->createTable($pace, SportProfile::CYCLING)
        );

        $this->assertStringContainsString('>' . __('Speed') . '<', $header);
        $this->assertStringNotContainsString('>' . __('Pace') . '<', $header);
    }

    public function testHeaderShowsSpeedLabelForMeterPerSecondUnit(): void
    {
        $pace = new Pace(0, 1);
        $pace->setUnit(new MeterPerSecond());

        $header = $this->renderHeader(
            $this->createTable($pace, SportProfile::SWIMMING)
        );

        $this->assertStringContainsString('>' . __('Speed') . '<', $header);
        $this->assertStringNotContainsString('>' . __('Pace') . '<', $header);
    }

    public function testHeaderDoesNotShowPaceForBikingActivityWithKmPerHour(): void
    {
        $demandedPace = new Pace(0, 1);
        $demandedPace->setUnit(new KmPerHour());

        $table = $this->createTable($demandedPace, SportProfile::CYCLING);
        $header = $this->renderHeader($table);

        $this->assertStringNotContainsString(
            '>' . __('Pace') . '<',
            $header,
            'Header must not display "Pace" for a speed-based unit (km/h) such as cycling activities.'
        );
    }

    public function testHeaderLabelIsIndependentOfEmptyPaceValue(): void
    {
        $emptyPace = new Pace(0, 0); // isEmpty() === true
        $emptyPace->setUnit(new KmPerHour());

        $header = $this->renderHeader($this->createTable($emptyPace, SportProfile::CYCLING));

        $this->assertStringContainsString('>' . __('Speed') . '<', $header);
    }
}
