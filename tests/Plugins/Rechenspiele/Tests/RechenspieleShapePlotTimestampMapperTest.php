<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapePlotTimestampMapper;

#[Group('plugin')]
final class RechenspieleShapePlotTimestampMapperTest extends TestCase
{
    public function testMapsSparseSeriesToLocalNoonWithWarmup(): void
    {
        $day = $this->day('2026-11-23', 'UTC');
        $result = new RechenspieleShapePlotTimestampMapper()->map(
            [181 => 12.0, 182 => 24.0, 184 => -3],
            $day,
            182
        );

        $this->assertSame([
            (string) ($day->modify('-1 day')->setTime(12, 0)->getTimestamp() * 1000) => 12.0,
            (string) ($day->setTime(12, 0)->getTimestamp() * 1000) => 24.0,
            (string) ($day->modify('+2 days')->setTime(12, 0)->getTimestamp() * 1000) => -3,
        ], $result);
    }

    public function testSpringDstTransitionUsesCalendarDaysNotFixedSecondOffsets(): void
    {
        $day = $this->day('2026-03-28', 'Europe/Berlin');
        $mapper = new RechenspieleShapePlotTimestampMapper();

        $first = (int) $mapper->timestampForIndex($day, 0, 0);
        $second = (int) $mapper->timestampForIndex($day, 0, 1);
        $third = (int) $mapper->timestampForIndex($day, 0, 2);

        $this->assertSame(86400000, $second - $first);
        $this->assertSame(82800000, $third - $second);
        $this->assertSame(
            new DateTimeImmutable('2026-03-30 12:00:00', new DateTimeZone('Europe/Berlin'))->getTimestamp() * 1000,
            $third
        );
    }

    public function testAutumnDstTransitionRetainsLocalNoon(): void
    {
        $day = $this->day('2026-10-24', 'Europe/Berlin');
        $mapper = new RechenspieleShapePlotTimestampMapper();

        $first = (int) $mapper->timestampForIndex($day, 0, 0);
        $second = (int) $mapper->timestampForIndex($day, 0, 1);

        $this->assertSame(90000000, $second - $first);
    }

    public function testEmptySeriesStaysEmpty(): void
    {
        $this->assertSame(
            [],
            new RechenspieleShapePlotTimestampMapper()->map([], $this->day('2026-11-23', 'UTC'), 0)
        );
    }

    #[DataProvider('invalidIndices')]
    public function testRejectsInvalidIndices(int $warmup, int $index): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RechenspieleShapePlotTimestampMapper()->timestampForIndex(
            $this->day('2026-11-23', 'UTC'),
            $warmup,
            $index
        );
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function invalidIndices(): iterable
    {
        yield 'negative warmup' => [-1, 0];
        yield 'negative index' => [0, -1];
    }

    private function day(string $date, string $timezone): DateTimeImmutable
    {
        return new DateTimeImmutable($date, new DateTimeZone($timezone));
    }
}
