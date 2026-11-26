<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleShapeSeriesCalculator;
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
        $this->assertNull($result['recomputed_maxima']);
    }

    public function testBanisterModelUsesFixedNormalizationAndDisablesPercentMode(): void
    {
        $trimp = [0.0, 50.0, 0.0, 100.0, 25.0, 0.0];
        $model = new BanisterModel($trimp, 42, 7, 1, 3);
        $model->calculate();

        $result = $this->calculate($trimp, 'banister', useCalculatedMaxima: true);

        $this->assertSame((float) $model->fatigueAt(3), $result['atl'][3]);
        $this->assertSame((float) $model->fitnessAt(3), $result['ctl'][3]);
        $this->assertSame((float) $model->performanceAt(3), $result['tsb'][3]);
        $this->assertSame(100, $result['max_atl']);
        $this->assertSame(100, $result['max_ctl']);
        $this->assertFalse($result['show_in_percent']);
        $this->assertNull($result['recomputed_maxima']);
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

    #[DataProvider('plotUnits')]
    public function testAllYearsKeepsRawTsbMaximaIndependentOfPlotUnits(bool $showInPercent): void
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
            $showInPercent,
            1.0
        );

        $this->assertSame($showInPercent ? $model->maxFatigue() : 100, $result['max_atl']);
        $this->assertSame($showInPercent ? $model->maxFitness() : 100, $result['max_ctl']);
        $this->assertSame([
            'atl' => $model->maxFatigue(),
            'ctl' => $model->maxFitness(),
        ], $result['recomputed_maxima']);
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function plotUnits(): iterable
    {
        yield 'percentage' => [true];
        yield 'absolute' => [false];
    }

    /**
     * @param list<float> $trimp
     * @param list<float> $weighted
     * @param list<float> $durations
     */
    #[DataProvider('invalidInputs')]
    public function testRejectsInvalidModelAndIncompleteDailyHistory(
        array $trimp,
        array $weighted,
        array $durations,
        string $model,
        int $first,
        int $last
    ): void {
        $this->expectException(InvalidArgumentException::class);

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
    }

    /**
     * @return iterable<string, array{list<float>, list<float>, list<float>, string, int, int}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'unknown model' => [[1.0, 2.0], [0.0, 0.0], [0.0, 0.0], 'other', 0, 1];
        yield 'incorrect model case' => [[1.0, 2.0], [0.0, 0.0], [0.0, 0.0], 'TSB', 0, 1];
        yield 'missing weighted values' => [[1.0, 2.0], [0.0], [0.0, 0.0], 'tsb', 0, 1];
        yield 'missing durations' => [[1.0, 2.0], [0.0, 0.0], [0.0], 'tsb', 0, 1];
        yield 'range exceeds history' => [[1.0, 2.0], [0.0, 0.0], [0.0, 0.0], 'tsb', 0, 2];
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
        float $correction = 1.0,
        bool $useCalculatedMaxima = false
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
            $useCalculatedMaxima,
            true,
            $correction
        );
    }
}
