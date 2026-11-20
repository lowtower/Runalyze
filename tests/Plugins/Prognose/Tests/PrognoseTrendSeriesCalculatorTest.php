<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Prognose\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Calculation\BasicEndurance;
use Runalyze\Plugins\Prognose\PrognoseTrendSeriesCalculator;
use Runalyze\Sports\Running\Prognosis\VO2max;

#[Group('plugin')]
final class PrognoseTrendSeriesCalculatorTest extends TestCase
{
    public function testEmptyHistoryReturnsEmptySeries(): void
    {
        $this->assertSame([
            'prognosis' => [],
            'prognosis_with_basic_endurance' => [],
        ], new PrognoseTrendSeriesCalculator()->calculate(
            [],
            10.0,
            new BasicEndurance(),
            1.0,
            30,
            '2026-01-10'
        ));
    }

    public function testHistoryWithoutValidVO2maxProducesNoPredictions(): void
    {
        $this->assertSame([
            'prognosis' => [],
            'prognosis_with_basic_endurance' => [],
        ], new PrognoseTrendSeriesCalculator()->calculate(
            [$this->row('2026-01-10', 0, 10.0, 0.0, 0.0)],
            10.0,
            new BasicEndurance(),
            1.0,
            30,
            '2026-01-10'
        ));
    }

    public function testValidHistoryCalculatesBothPredictionSeries(): void
    {
        $today = date('Y-m-d');
        $key = strtotime($today . ' 12:00') . '000';

        $result = new PrognoseTrendSeriesCalculator()->calculate(
            [$this->row($today, 0, 10.0, 162000.0, 3600.0)],
            10.0,
            new BasicEndurance(),
            1.0,
            30,
            $today
        );

        $this->assertArrayHasKey($key, $result['prognosis']);
        $this->assertArrayHasKey($key, $result['prognosis_with_basic_endurance']);
        $this->assertGreaterThan(0, $result['prognosis'][$key]);
        $this->assertGreaterThan(0, $result['prognosis_with_basic_endurance'][$key]);
    }

    public function testInitialTrainingContributesOnlyOnceToTheWeightedVO2maxAverage(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $key = strtotime($today . ' 12:00') . '000';

        $result = new PrognoseTrendSeriesCalculator()->calculate(
            [
                $this->row($yesterday, -1, 10.0, 45.0 * 3600, 3600.0),
                $this->row($today, 0, 10.0, 55.0 * 3600, 3600.0),
            ],
            10.0,
            new BasicEndurance(),
            1.0,
            30,
            $today
        );

        $expectedSeconds = VO2max::getPrognosisInSecondsFor(50.0, 10.0);
        $this->assertNotNull($expectedSeconds);
        $this->assertArrayHasKey($key, $result['prognosis']);
        $this->assertEqualsWithDelta($expectedSeconds * 1000, $result['prognosis'][$key], 0.1);
    }

    public function testOlderHistoryAppendsCurrentDayAndExpiresVO2maxWindow(): void
    {
        $today = date('Y-m-d');
        $oldDate = date('Y-m-d', strtotime('-40 days'));
        $currentKey = strtotime($today . ' 12:00') . '000';

        $result = new PrognoseTrendSeriesCalculator()->calculate(
            [$this->row($oldDate, -40, 10.0, 162000.0, 3600.0)],
            10.0,
            new BasicEndurance(),
            1.0,
            30,
            $today
        );

        $this->assertCount(1, $result['prognosis']);
        $this->assertArrayNotHasKey($currentKey, $result['prognosis']);
    }

    /**
     * @return array{date_age: int, date: string, distance: float, vo2max_weighted: float, vo2max_sum_time: float}
     */
    private function row(string $date, int $age, float $distance, float $weighted, float $seconds): array
    {
        return [
            'date_age' => $age,
            'date' => $date,
            'distance' => $distance,
            'vo2max_weighted' => $weighted,
            'vo2max_sum_time' => $seconds,
        ];
    }
}
