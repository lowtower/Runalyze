<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Rechenspiele\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Rechenspiele\RechenspieleShapeSeriesCalculator;
use Runalyze\Sports\Performance\Model\BanisterModel;
use Runalyze\Sports\Performance\Model\TsbModel;

#[Group('plugin')]
final class RechenspieleShapeSeriesCalculatorTest extends TestCase
{
    public function testTsbSeriesReuseExistingPerformanceModelAndTrimpHistory(): void
    {
        $trimp = [0.0, 50.0, 0.0, 100.0, 25.0, 0.0];
        $model = new TsbModel($trimp, 42, 7);
        $model->calculate();

        $result = $this->calculate($trimp);

        $this->assertSame(100.0 * $model->fatigueAt(3) / 100, $result['atl'][3]);
        $this->assertSame(100.0 * $model->fitnessAt(3) / 80, $result['ctl'][3]);
        $this->assertSame(100.0 * $model->performanceAt(3) / 80, $result['tsb'][3]);
        $this->assertSame([
            2 => 0.0,
            3 => 100.0,
            4 => 25.0,
        ], $result['trimp']);
        $this->assertEqualsWithDelta(100.0, $result['max_trimp'], PHP_FLOAT_EPSILON);
        $this->assertTrue($result['show_in_percent']);
    }

    public function testBanisterModelUsesFixedNormalizationAndDisablesPercentMode(): void
    {
        $trimp = [0.0, 50.0, 0.0, 100.0, 25.0, 0.0];
        $model = new BanisterModel($trimp, 42, 7, 1, 3);
        $model->calculate();

        $result = $this->calculate($trimp, 'banister');

        $this->assertSame((float) $model->fatigueAt(3), $result['atl'][3]);
        $this->assertSame((float) $model->fitnessAt(3), $result['ctl'][3]);
        $this->assertSame((float) $model->performanceAt(3), $result['tsb'][3]);
        $this->assertSame(100, $result['max_atl']);
        $this->assertSame(100, $result['max_ctl']);
        $this->assertFalse($result['show_in_percent']);
    }

    public function testRollingVo2maxExcludesCurrentDayAndDailyValueUsesCurrentDay(): void
    {
        $result = $this->calculate(
            [0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
            'tsb',
            [0.0, 120.0, 0.0, 240.0, 180.0, 0.0],
            [0.0, 30.0, 0.0, 40.0, 30.0, 0.0],
            2.0
        );

        $this->assertEqualsWithDelta(8.0, $result['vo2max'][2], 0.00001);
        $this->assertEqualsWithDelta(12.0, $result['vo2max'][4], 0.00001);
        $this->assertEqualsWithDelta(12.0, $result['vo2max_day'][3], 0.00001);
        $this->assertEqualsWithDelta(12.0, $result['vo2max_day'][4], 0.00001);
        $this->assertArrayNotHasKey(2, $result['vo2max_day']);
    }

    public function testEmptyVo2maxDurationsDoNotProduceInvalidValues(): void
    {
        $result = $this->calculate(
            [0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
            'tsb',
            [0.0, 0.0, 0.0, 10.0, 0.0, 0.0],
            [0.0, 0.0, 0.0, 0.0, 0.0, 0.0]
        );

        $this->assertSame([], $result['vo2max']);
        $this->assertSame([], $result['vo2max_day']);
    }

    public function testAllYearsUsesCalculatedModelMaxima(): void
    {
        $trimp = [0.0, 50.0, 0.0, 100.0, 25.0, 0.0];
        $model = new TsbModel($trimp, 42, 7);
        $model->calculate();

        $result = new RechenspieleShapeSeriesCalculator()->calculate(
            $trimp,
            array_fill(0, count($trimp), 0.0),
            array_fill(0, count($trimp), 0.0),
            'tsb',
            42,
            7,
            2,
            2,
            4,
            1,
            1,
            true,
            true,
            1.0
        );

        $this->assertSame(max(1, $model->maxFatigue()), $result['max_atl']);
        $this->assertSame(max(1, $model->maxFitness()), $result['max_ctl']);
    }

    public function testRejectsInvalidModelAndIncompleteDailyHistory(): void
    {
        foreach (['other', 'TSB'] as $model) {
            $this->expectInvalidInput([1.0, 2.0], [0.0, 0.0], [0.0, 0.0], $model, 0, 1);
        }

        $this->expectInvalidInput([1.0, 2.0], [0.0], [0.0, 0.0], 'tsb', 0, 1);
        $this->expectInvalidInput([1.0, 2.0], [0.0, 0.0], [0.0], 'tsb', 0, 1);
        $this->expectInvalidInput([1.0, 2.0], [0.0, 0.0], [0.0, 0.0], 'tsb', 0, 2);
    }

    /**
     * @param list<float> $trimp
     * @param list<float> $weighted
     * @param list<float> $durations
     */
    private function expectInvalidInput(
        array $trimp,
        array $weighted,
        array $durations,
        string $model,
        int $first,
        int $last
    ): void {
        try {
            new RechenspieleShapeSeriesCalculator()->calculate(
                $trimp,
                $weighted,
                $durations,
                $model,
                42,
                7,
                2,
                $first,
                $last,
                100,
                80,
                false,
                true,
                1.0
            );

            self::fail('Expected invalid input to be rejected.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
    }

    /**
     * @param list<float> $trimp
     * @param list<float>|null $weighted
     * @param list<float>|null $durations
     */
    private function calculate(
        array $trimp,
        string $model = 'tsb',
        ?array $weighted = null,
        ?array $durations = null,
        float $correction = 1.0
    ): array {
        return new RechenspieleShapeSeriesCalculator()->calculate(
            $trimp,
            $weighted ?? array_fill(0, count($trimp), 0.0),
            $durations ?? array_fill(0, count($trimp), 0.0),
            $model,
            42,
            7,
            2,
            2,
            4,
            100,
            80,
            false,
            true,
            $correction
        );
    }
}
