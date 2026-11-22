<?php

declare(strict_types=1);

namespace Runalyze\Plugins\Goals\Tests;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Runalyze\Plugins\Goals\GoalsProgressCalculator;

#[Group('plugin')]
final class GoalsProgressCalculatorTest extends TestCase
{
    private GoalsProgressCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new GoalsProgressCalculator();
    }

    public function testReturnsNoRowsWithoutDistanceOrGoal(): void
    {
        $this->assertSame([], $this->calculate(distanceSum: 0, goal: 0, showDetails: true));
    }

    public function testDistanceWithoutGoalProducesCurrentAveragesAndProjectedActivities(): void
    {
        $rows = $this->calculate(distanceSum: 28.0, activityCount: 4, goal: 0, showDetails: true);

        $this->assertSame(['Current', 'avg. Day', 'avg. Week', 'Prognosis'], array_column($rows, 'name'));
        $this->assertSame([
            'name' => 'Current',
            'lvl' => 1,
            'sep' => false,
            'km' => 28.0,
            'anz' => 4.0,
        ], $rows[0]);
        $this->assertEqualsWithDelta(2.0, $rows[1]['km'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(14.0, $rows[2]['km'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(2.0, $rows[2]['anz'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(60.0, $rows[3]['km'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(60.0 / 28.0 * 4.0, $rows[3]['anz'], 0.00001);
    }

    public function testRemainingGoalIncludesDailyAndWeeklyTargetsAndBothPaceBunnyBars(): void
    {
        $rows = $this->calculate(distanceSum: 28.0, activityCount: 4, goal: 100.0, showDetails: true);
        $names = array_column($rows, 'name');

        $this->assertContains('Goal', $names);
        $this->assertContains('16 days left', $names);
        $this->assertContains('Pace Bunny', $names);
        $this->assertContains('Behind', $names);
        $this->assertContains('Head 2 Head', $names);
        $this->assertContains('Me', $names);
        $this->assertContains('Pace&nbsp;Bunny', $names);

        $behindIndex = array_search('Behind', $names, true);
        $this->assertIsInt($behindIndex);
        $this->assertEqualsWithDelta(100.0 / 30.0 * 14.0 - 28.0, $rows[$behindIndex]['km'], 0.00001);

        $me = $rows[count($rows) - 2];
        $bunny = $rows[count($rows) - 1];
        $this->assertSame('#f99', $me['color']);
        $this->assertEqualsWithDelta(28.0, $me['val'], PHP_FLOAT_EPSILON);
        $this->assertEqualsWithDelta(100.0, $me['max'], PHP_FLOAT_EPSILON);
        $this->assertSame('#ccf', $bunny['color']);
        $this->assertSame('#ddf', $bunny['addcolor']);
        $this->assertEqualsWithDelta(100.0 / 30.0, $bunny['add'], 0.00001);
    }

    public function testGoalReachedUsesAheadColorAndKeepsBothBars(): void
    {
        $rows = $this->calculate(distanceSum: 120.0, activityCount: 12, goal: 100.0, showDetails: true);

        $this->assertContains('Goal reached.', array_column($rows, 'name'));
        $this->assertContains('Ahead', array_column($rows, 'name'));
        $this->assertSame('#9f9', $rows[count($rows) - 2]['color']);
        $this->assertCount(2, array_filter($rows, static fn (array $row): bool => ($row['type'] ?? '') === 'bar'));
    }

    public function testWithoutDetailsRendersOnlyCurrentGoalAndBars(): void
    {
        $rows = $this->calculate(distanceSum: 28.0, activityCount: 4, goal: 100.0, showDetails: false);

        $this->assertSame([
            'Current',
            'Goal',
            '16 days left',
            'Me',
            'Pace&nbsp;Bunny',
        ], array_column($rows, 'name'));
    }

    public function testSevenDayBoundaryOmitsWeeklyAverages(): void
    {
        $rows = $this->calculator->calculate(
            new DateTimeImmutable('2026-04-01'),
            new DateTimeImmutable('2026-04-30'),
            new DateTimeImmutable('2026-04-07'),
            14.0,
            2,
            100.0,
            true
        );

        $this->assertCount(2, array_filter(
            $rows,
            static fn (array $row): bool => $row['name'] === 'avg. Day'
        ));
        $this->assertNotContains('avg. Week', array_column($rows, 'name'));
    }

    public function testPastGoalAvoidsDivisionByZeroForRemainingDailyTarget(): void
    {
        $rows = $this->calculator->calculate(
            new DateTimeImmutable('2026-04-01'),
            new DateTimeImmutable('2026-04-30'),
            new DateTimeImmutable('2026-05-03'),
            25.0,
            2,
            100.0,
            true
        );

        $this->assertContains('-3 days left', array_column($rows, 'name'));
        $dayTargets = array_values(array_filter(
            $rows,
            static fn (array $row): bool => $row['name'] === 'avg. Day'
        ));
        $this->assertEqualsWithDelta(0.0, $dayTargets[1]['km'], PHP_FLOAT_EPSILON);
    }

    public function testGoalWithNoActivitiesStillShowsProgressBars(): void
    {
        $rows = $this->calculate(distanceSum: 0, goal: 100.0, showDetails: false);

        $this->assertSame(['Goal', '16 days left', 'Me', 'Pace&nbsp;Bunny'], array_column($rows, 'name'));
        $this->assertEqualsWithDelta(0.0, $rows[2]['val'], PHP_FLOAT_EPSILON);
    }

    /**
     * @return list<array<string, int|float|string|bool|null>>
     */
    private function calculate(float $distanceSum, int $activityCount = 0, float $goal = 0, bool $showDetails = true): array
    {
        return $this->calculator->calculate(
            new DateTimeImmutable('2026-04-01'),
            new DateTimeImmutable('2026-04-30'),
            new DateTimeImmutable('2026-04-14'),
            $distanceSum,
            $activityCount,
            $goal,
            $showDetails
        );
    }
}
