<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotDateRange;

#[Group('plugin')]
final class RechenspieleShapePlotDateRangeTest extends TestCase
{
    public function testAllHistoryStartsAtFirstActivityAndIncludesFirstPlotIndex(): void
    {
        $range = $this->resolve('all');

        $this->assertSame('2024-02-01', $range['start_day']->format('Y-m-d'));
        $this->assertSame('2026-11-23', $range['end_day']->format('Y-m-d'));
        $this->assertSame(182, $range['warmup_days']);
        $this->assertSame(182, $range['lowest_index']);
        $this->assertSame(182 + $range['number_of_days'], $range['highest_index']);
        $this->assertTrue($range['calculate_maxima']);
        $this->assertNull($range['limited_year']);
    }

    public function testLastHalfYearExcludesItsFirstPlotDayAndShowsFutureDays(): void
    {
        $range = $this->resolve('lasthalf');
        $today = $this->day('2026-11-23');

        $this->assertSame($today->modify('-180 days')->format('Y-m-d'), $range['start_day']->format('Y-m-d'));
        $this->assertSame('2026-12-23', $range['end_day']->format('Y-m-d'));
        $this->assertSame(183, $range['lowest_index']);
        $this->assertFalse($range['calculate_maxima']);
        $this->assertTrue($range['mark_future']);
    }

    public function testLastYearUsesCalendarYearArithmetic(): void
    {
        $range = $this->resolve('lastyear');

        $this->assertSame('2025-11-23', $range['start_day']->format('Y-m-d'));
        $this->assertSame('2026-12-23', $range['end_day']->format('Y-m-d'));
        $this->assertSame($range['lowest_index'] + $range['number_of_days'], $range['highest_index']);
    }

    public function testSelectedYearIncludesLeapDayAndLimitsXAxis(): void
    {
        $range = $this->resolve('2024');

        $this->assertSame('2024-01-01', $range['start_day']->format('Y-m-d'));
        $this->assertSame('2024-12-31', $range['end_day']->format('Y-m-d'));
        $this->assertSame(365, $range['number_of_days']);
        $this->assertSame(2024, $range['limited_year']);
        $this->assertFalse($range['mark_future']);
    }

    public function testDateRangePreservesLocalCalendarAcrossDst(): void
    {
        $timezone = new DateTimeZone('Europe/Berlin');
        $range = new RechenspieleShapePlotDateRange()->resolve(
            'lasthalf',
            new DateTimeImmutable('2024-02-01', $timezone),
            new DateTimeImmutable('2026-03-29 12:00:00', $timezone),
            7,
            42,
            30,
            182,
            70
        );

        $this->assertSame('Europe/Berlin', $range['start_day']->getTimezone()->getName());
        $this->assertSame('Europe/Berlin', $range['end_day']->getTimezone()->getName());
        $this->assertSame(210, $range['number_of_days']);
    }

    #[DataProvider('invalidSelections')]
    public function testRejectsInvalidRanges(string $rangeName, int $atlDays): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RechenspieleShapePlotDateRange()->resolve(
            $rangeName,
            $this->day('2024-02-01'),
            $this->day('2026-11-23'),
            $atlDays,
            42,
            30,
            182,
            70
        );
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function invalidSelections(): iterable
    {
        yield 'unknown range' => ['nextmonth', 7];
        yield 'year before account history' => ['2023', 7];
        yield 'future year' => ['2027', 7];
        yield 'invalid ATL window' => ['all', 0];
    }

    public function testRejectsHistoryStartingAfterToday(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RechenspieleShapePlotDateRange()->resolve(
            'all',
            $this->day('2026-12-01'),
            $this->day('2026-11-23'),
            7,
            42,
            30,
            182,
            70
        );
    }

    /**
     * @return array{
     *     start_day: DateTimeImmutable,
     *     end_day: DateTimeImmutable,
     *     warmup_days: int,
     *     lowest_index: int,
     *     highest_index: int,
     *     number_of_days: int,
     *     calculate_maxima: bool,
     *     limited_year: ?int,
     *     mark_future: bool
     * }
     */
    private function resolve(string $selection): array
    {
        return new RechenspieleShapePlotDateRange()->resolve(
            $selection,
            $this->day('2024-02-01'),
            $this->day('2026-11-23'),
            7,
            42,
            30,
            182,
            70
        );
    }

    private function day(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
