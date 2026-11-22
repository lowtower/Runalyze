<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Goals\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Goals\GoalsTimeRangeProvider;

#[Group('plugin')]
final class GoalsTimeRangeProviderTest extends TestCase
{
    public function testGeneratesExpectedRangesAndNames(): void
    {
        $ranges = new GoalsTimeRangeProvider()->getRanges(new DateTimeImmutable('2026-11-22 12:00:00'), true);

        $this->assertSame(['woche', 'mon', 'hj', 'saison', 'jahr'], array_keys($ranges));
        $this->assertSame('2026-11-16', $ranges['woche']['start']->format('Y-m-d'));
        $this->assertSame('2026-11-22 23:59:50', $ranges['woche']['end']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-01', $ranges['mon']['start']->format('Y-m-d'));
        $this->assertSame('2026-11-30', $ranges['mon']['end']->format('Y-m-d'));
        $this->assertSame('2026-07-01', $ranges['hj']['start']->format('Y-m-d'));
        $this->assertSame('2026-12-31', $ranges['hj']['end']->format('Y-m-d'));
        $this->assertSame('2026-01-01', $ranges['jahr']['start']->format('Y-m-d'));
        $this->assertSame('2026-12-31', $ranges['jahr']['end']->format('Y-m-d'));
        $this->assertArrayHasKey('note', $ranges['saison']);

        foreach ($ranges as $range) {
            $this->assertLessThanOrEqual($range['end'], $range['start']);
        }
    }

    public function testSundayCanBeFirstDayOfWeek(): void
    {
        $ranges = new GoalsTimeRangeProvider()->getRanges(new DateTimeImmutable('2026-11-22 12:00:00'), false);

        $this->assertSame('2026-11-22', $ranges['woche']['start']->format('Y-m-d'));
        $this->assertSame('2026-11-28 23:59:50', $ranges['woche']['end']->format('Y-m-d H:i:s'));
    }

    public function testFirstHalfOfYearUsesFirstSeasonAndJanuaryToJune(): void
    {
        $ranges = new GoalsTimeRangeProvider()->getRanges(new DateTimeImmutable('2026-03-15 12:00:00'), true);

        $this->assertSame('2026-01-01', $ranges['hj']['start']->format('Y-m-d'));
        $this->assertSame('2026-06-30', $ranges['hj']['end']->format('Y-m-d'));
        $this->assertSame('2025-12-29', $ranges['saison']['start']->format('Y-m-d'));
        $this->assertSame('2026-06-28', $ranges['saison']['end']->format('Y-m-d'));
    }

    public function testSeasonWithoutIsoWeekFiftyThreeEndsOnLastSundayOfWeekFiftyTwo(): void
    {
        $ranges = new GoalsTimeRangeProvider()->getRanges(new DateTimeImmutable('2025-11-15 12:00:00'), true);

        $this->assertSame('2025-06-30', $ranges['saison']['start']->format('Y-m-d'));
        $this->assertSame('2025-12-28', $ranges['saison']['end']->format('Y-m-d'));
        $this->assertArrayNotHasKey('note', $ranges['jahr']);
    }

    public function testWeekStartIsStableAcrossYearBoundary(): void
    {
        $provider = new GoalsTimeRangeProvider();
        $now = new DateTimeImmutable('2027-01-01 12:00:00');

        $mondayRanges = $provider->getRanges($now, true);
        $sundayRanges = $provider->getRanges($now, false);

        $this->assertSame('2026-12-28', $mondayRanges['woche']['start']->format('Y-m-d'));
        $this->assertSame('2027-01-03', $mondayRanges['woche']['end']->format('Y-m-d'));
        $this->assertSame('2026-12-27', $sundayRanges['woche']['start']->format('Y-m-d'));
        $this->assertSame('2027-01-02', $sundayRanges['woche']['end']->format('Y-m-d'));
        $this->assertSame('2027-01-01', $mondayRanges['jahr']['start']->format('Y-m-d'));
    }

    public function testLeapYearFebruaryAndFirstSeasonAreHandled(): void
    {
        $ranges = new GoalsTimeRangeProvider()->getRanges(new DateTimeImmutable('2024-02-29 12:00:00'), true);

        $this->assertSame('2024-02-01', $ranges['mon']['start']->format('Y-m-d'));
        $this->assertSame('2024-02-29', $ranges['mon']['end']->format('Y-m-d'));
        $this->assertSame('2024-01-01', $ranges['saison']['start']->format('Y-m-d'));
        $this->assertSame('2024-06-30', $ranges['saison']['end']->format('Y-m-d'));
    }

    public function testDateRangesRetainTheProvidedTimezone(): void
    {
        $timezone = new \DateTimeZone('Pacific/Auckland');
        $ranges = new GoalsTimeRangeProvider()->getRanges(
            new DateTimeImmutable('2026-08-20 12:00:00', $timezone),
            true
        );

        foreach ($ranges as $range) {
            $this->assertSame($timezone->getName(), $range['start']->getTimezone()->getName());
            $this->assertSame($timezone->getName(), $range['end']->getTimezone()->getName());
        }
    }

    public function testSecondSeasonStartsWithIsoWeekTwentySeven(): void
    {
        $ranges = new GoalsTimeRangeProvider()->getRanges(new DateTimeImmutable('2026-11-22 12:00:00'), true);

        $this->assertSame('2026-06-29', $ranges['saison']['start']->format('Y-m-d'));
        $this->assertSame('2027-01-03', $ranges['saison']['end']->format('Y-m-d'));
    }
}
