<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotSeriesAssembler;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapePlotTimestampMapper;

#[Group('plugin')]
final class RechenspieleShapePlotSeriesAssemblerTest extends TestCase
{
    public function testAlignsEveryPlotSeriesOnTheSameLocalCalendarDays(): void
    {
        $day = new DateTimeImmutable('2026-03-28', new DateTimeZone('Europe/Berlin'));
        $mapper = new RechenspieleShapePlotTimestampMapper();
        $result = $this->assembler()->assemble(
            [
                'atl' => [
                    2 => 24.0,
                    3 => 25.0,
                ],
                'ctl' => [
                    2 => 18.0,
                ],
                'tsb' => [
                    2 => -6.0,
                ],
                'trimp' => [
                    2 => 50.0,
                ],
                'vo2max' => [
                    3 => 45.0,
                ],
                'vo2max_day' => [
                    2 => 46.0,
                ],
                'max_atl' => 80,
                'max_ctl' => 60,
                'max_trimp' => 50.0,
                'show_in_percent' => true,
            ],
            [
                3 => 70,
            ],
            $day,
            2
        );

        $saturday = $mapper->timestampForIndex($day, 2, 2);
        $sunday = $mapper->timestampForIndex($day, 2, 3);

        $this->assertSame([
            $saturday => 24.0,
            $sunday => 25.0,
        ], $result['atl']);
        $this->assertSame([
            $saturday => 18.0,
        ], $result['ctl']);
        $this->assertSame([
            $saturday => -6.0,
        ], $result['tsb']);
        $this->assertSame([
            $saturday => 50.0,
        ], $result['trimp']);
        $this->assertSame([
            $sunday => 45.0,
        ], $result['vo2max']);
        $this->assertSame([
            $saturday => 46.0,
        ], $result['vo2max_day']);
        $this->assertSame([
            $sunday => 70,
        ], $result['marathon_shape']);
        $this->assertSame(80, $result['max_atl']);
        $this->assertSame(60, $result['max_ctl']);
        $this->assertEqualsWithDelta(50.0, $result['max_trimp'], PHP_FLOAT_EPSILON);
        $this->assertTrue($result['show_in_percent']);
        $this->assertSame(82800000, (int) $sunday - (int) $saturday);
    }

    public function testKeepsMissingSeriesEmptyAndPreservesAbsoluteNormalization(): void
    {
        $result = $this->assembler()->assemble(
            [
                'atl' => [],
                'ctl' => [],
                'tsb' => [],
                'trimp' => [],
                'vo2max' => [],
                'vo2max_day' => [],
                'max_atl' => 100,
                'max_ctl' => 100,
                'max_trimp' => 0.0,
                'show_in_percent' => false,
            ],
            [],
            new DateTimeImmutable('2026-11-23', new DateTimeZone('UTC')),
            0
        );

        $this->assertSame([], $result['atl']);
        $this->assertSame([], $result['vo2max_day']);
        $this->assertSame([], $result['marathon_shape']);
        $this->assertFalse($result['show_in_percent']);
    }

    public function testRejectsNegativeWarmupWithoutDroppingSeries(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->assembler()->assemble(
            [
                'atl' => [
                    0 => 10.0,
                ],
                'ctl' => [],
                'tsb' => [],
                'trimp' => [],
                'vo2max' => [],
                'vo2max_day' => [],
                'max_atl' => 100,
                'max_ctl' => 100,
                'max_trimp' => 0.0,
                'show_in_percent' => false,
            ],
            [],
            new DateTimeImmutable('2026-11-23', new DateTimeZone('UTC')),
            -1
        );
    }

    private function assembler(): RechenspieleShapePlotSeriesAssembler
    {
        return new RechenspieleShapePlotSeriesAssembler(new RechenspieleShapePlotTimestampMapper());
    }
}
