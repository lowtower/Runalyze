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

    public function testSecondSeasonStartsWithIsoWeekTwentySeven(): void
    {
        $ranges = new GoalsTimeRangeProvider()->getRanges(new DateTimeImmutable('2026-11-22 12:00:00'), true);

        $this->assertSame('2026-06-29', $ranges['saison']['start']->format('Y-m-d'));
        $this->assertSame('2027-01-03', $ranges['saison']['end']->format('Y-m-d'));
    }
}
