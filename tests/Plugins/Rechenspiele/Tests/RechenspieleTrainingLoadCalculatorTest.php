<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Rechenspiele\RechenspieleTrainingLoadCalculator;

#[Group('plugin')]
final class RechenspieleTrainingLoadCalculatorTest extends TestCase
{
    public function testCalculatesTrainingLoadFromKnownTrimpHistory(): void
    {
        $result = new RechenspieleTrainingLoadCalculator()->calculate(
            [
                -2 => 100.0,
                0 => 50.0,
            ],
            [10.0, 90.0, 20.0, 80.0, 15.0, 85.0, 30.0],
            42,
            7,
            50,
            30
        );

        $this->assertSame(27, $result['atl']);
        $this->assertSame(7, $result['ctl']);
        $this->assertSame(-20, $result['tsb']);
        $this->assertSame(54, $result['atl_percent']);
        $this->assertSame(23, $result['ctl_percent']);
        $this->assertSame(-74, $result['tsb_percent']);
        $this->assertSame(50, $result['max_atl']);
        $this->assertSame(30, $result['max_ctl']);
        $this->assertGreaterThanOrEqual(0, $result['rest_days']);
        $this->assertGreaterThan(0.0, $result['monotony']);
        $this->assertGreaterThan(0, $result['training_strain']);
        $this->assertGreaterThanOrEqual(0.0, $result['training_strain_percent']);
    }

    public function testEmptyHistoryHasNoLoadAndDoesNotDivideByZero(): void
    {
        $result = new RechenspieleTrainingLoadCalculator()->calculate([], [], 42, 7, 0, 0);

        $this->assertSame(0, $result['atl']);
        $this->assertSame(0, $result['ctl']);
        $this->assertSame(0, $result['tsb']);
        $this->assertSame(0, $result['atl_percent']);
        $this->assertSame(0, $result['ctl_percent']);
        $this->assertSame(0, $result['tsb_percent']);
        $this->assertSame(1, $result['max_atl']);
        $this->assertSame(1, $result['max_ctl']);
        $this->assertSame(0, $result['rest_days']);
        $this->assertSame(0, $result['easy_trimp']);
        $this->assertEqualsWithDelta(0.0, $result['monotony'], PHP_FLOAT_EPSILON);
        $this->assertSame(0, $result['training_strain']);
    }

    public function testCalculatedHistoricalMaximaRaiseOutdatedStoredMaxima(): void
    {
        $result = new RechenspieleTrainingLoadCalculator()->calculate(
            [
                -2 => 100.0,
                0 => 50.0,
            ],
            [50.0],
            42,
            7,
            1,
            1
        );

        $this->assertGreaterThan(1, $result['max_atl']);
        $this->assertGreaterThan(1, $result['max_ctl']);
        $this->assertLessThanOrEqual(100, $result['atl_percent']);
        $this->assertLessThanOrEqual(100, $result['ctl_percent']);
    }
}
