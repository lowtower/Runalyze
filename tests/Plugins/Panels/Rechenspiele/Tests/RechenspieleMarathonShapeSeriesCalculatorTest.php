<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Panels\Rechenspiele\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\Category\BasicEndurance;
use Runalyze\Plugins\Panels\Rechenspiele\RechenspieleMarathonShapeSeriesCalculator;
use Runalyze\Sports\Running\MarathonShape;

#[Group('plugin')]
final class RechenspieleMarathonShapeSeriesCalculatorTest extends TestCase
{
    public function testReusesMarathonShapeForWeeklyMileageAndWeightedLongJogPoints(): void
    {
        $configuration = $this->configuration();
        $distances = [0.0, 15.0, 20.0, 5.0, 0.0];
        $longjogs = [[], [15.0], [20.0], [], []];
        $shape = new MarathonShape(50.0, $configuration);
        $target = $shape->getTargetForLongJogEachWeek() - 10.0;
        $longJogPoints = (
            2 * (1 - 2 / 3) * (15.0 - 10.0) ** 2
            + 2 * (1 - 1 / 3) * (20.0 - 10.0) ** 2
        ) / $target ** 2;

        $result = $this->calculator()->calculate(
            $distances,
            $longjogs,
            [
                3 => 50.0,
            ],
            $configuration,
            1,
            4,
            100
        );

        $this->assertSame([
            3 => $shape->getShapeFor(35.0, $longJogPoints, 100),
        ], $result);
    }

    public function testDaysWithNoRollingVo2maxAreOmitted(): void
    {
        $result = $this->calculator()->calculate(
            [0.0, 0.0, 0.0],
            [[], [], []],
            [],
            $this->configuration(),
            1,
            2,
            100
        );

        $this->assertSame([], $result);
    }

    public function testActivityOnCurrentDayIsExcludedFromMileageButCountsAsLongJog(): void
    {
        $configuration = $this->configuration();
        $shape = new MarathonShape(50.0, $configuration);
        $target = $shape->getTargetForLongJogEachWeek() - 10.0;

        $result = $this->calculator()->calculate(
            [0.0, 0.0, 20.0, 30.0],
            [[], [], [], [30.0]],
            [
                3 => 50.0,
            ],
            $configuration,
            3,
            3,
            100
        );

        $this->assertSame(
            $shape->getShapeFor(20.0, 2 * 20.0 ** 2 / $target ** 2, 100),
            $result[3]
        );
    }

    public function testUsesAccountAgeToClampWeeklyMileageWindow(): void
    {
        $configuration = $this->configuration();
        $configuration->set('BE_DAYS_FOR_WEEK_KM', '10');
        $configuration->set('BE_DAYS_FOR_WEEK_KM_MIN', '2');

        $shape = new MarathonShape(40.0, $configuration);

        $result = $this->calculator()->calculate(
            [10.0, 20.0, 30.0, 40.0, 50.0],
            [[], [], [], [], []],
            [
                4 => 40.0,
            ],
            $configuration,
            4,
            4,
            3
        );

        $this->assertSame($shape->getShapeFor(90.0, 0.0, 3), $result[4]);
    }

    /**
     * @param list<float> $distances
     * @param list<list<float>> $longjogs
     * @param array<int, float> $vo2max
     */
    #[DataProvider('invalidInputs')]
    public function testRejectsInvalidShapeHistory(
        array $distances,
        array $longjogs,
        array $vo2max,
        int $first,
        int $last,
        int $accountAge
    ): void {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->calculate(
            $distances,
            $longjogs,
            $vo2max,
            $this->configuration(),
            $first,
            $last,
            $accountAge
        );
    }

    /**
     * @return iterable<string, array{list<float>, list<list<float>>, array<int, float>, int, int, int}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'missing long jog day' => [[0.0, 0.0], [[]], [
            1 => 50.0,
        ], 1, 1, 100];
        yield 'negative index' => [[0.0, 0.0], [[], []], [
            1 => 50.0,
        ], -1, 1, 100];
        yield 'reversed range' => [[0.0, 0.0], [[], []], [
            1 => 50.0,
        ], 1, 0, 100];
        yield 'index past history' => [[0.0, 0.0], [[], []], [
            1 => 50.0,
        ], 1, 2, 100];
        yield 'negative account age' => [[0.0, 0.0], [[], []], [
            1 => 50.0,
        ], 1, 1, -1];
    }

    public function testRejectsZeroLongJogPeriod(): void
    {
        $configuration = $this->configuration();
        $configuration->set('BE_DAYS_FOR_LONGJOGS', '0');

        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->calculate([0.0], [[]], [
            0 => 40.0,
        ], $configuration, 0, 0, 100);
    }

    private function configuration(): BasicEndurance
    {
        $configuration = new BasicEndurance();
        $configuration->set('BE_DAYS_FOR_LONGJOGS', '3');
        $configuration->set('BE_DAYS_FOR_WEEK_KM', '3');
        $configuration->set('BE_DAYS_FOR_WEEK_KM_MIN', '3');
        $configuration->set('BE_MIN_KM_FOR_LONGJOG', '10');

        return $configuration;
    }

    private function calculator(): RechenspieleMarathonShapeSeriesCalculator
    {
        return new RechenspieleMarathonShapeSeriesCalculator();
    }
}
