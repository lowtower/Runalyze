<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Model\Sport;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Model\Sport\SportStatistic;

final class SportStatisticTest extends TestCase
{
    private function createStatistic(): SportStatistic
    {
        return new SportStatistic($this->createStub(Sport::class));
    }

    public function testGetSportReturnsConstructorValue(): void
    {
        $sport = $this->createStub(Sport::class);

        $statistic = new SportStatistic($sport);

        $this->assertSame($sport, $statistic->getSport());
    }

    public function testNumberOfActivitiesDefaultsToNull(): void
    {
        $statistic = $this->createStatistic();

        $this->assertNull($statistic->getNumberOfActivities());
    }

    public function testSetAndGetNumberOfActivities(): void
    {
        $statistic = $this->createStatistic();

        $statistic->setNumberOfActivities(10);

        $this->assertSame(10, $statistic->getNumberOfActivities());
    }

    public function testNumberOfActivitiesWithDistanceDefaultsToNullWhenOmitted(): void
    {
        $statistic = $this->createStatistic();

        $statistic->setNumberOfActivities(10);

        $this->assertFalse($statistic->areMostActivitiesWithDistance());
    }

    public function testTotalDistanceDefaultsToNull(): void
    {
        $statistic = $this->createStatistic();

        $this->assertNull($statistic->getTotalDistance());
        $this->assertFalse($statistic->isTotalDistanceKnown());
    }

    public function testSetAndGetTotalDistance(): void
    {
        $statistic = $this->createStatistic();

        $statistic->setTotalDistance(42.5);

        $this->assertEqualsWithDelta(42.5, $statistic->getTotalDistance(), PHP_FLOAT_EPSILON);
        $this->assertTrue($statistic->isTotalDistanceKnown());
    }

    public function testSetTotalDistanceToNullMarksItAsUnknown(): void
    {
        $statistic = $this->createStatistic();

        $statistic->setTotalDistance(42.5);
        $statistic->setTotalDistance(null);

        $this->assertNull($statistic->getTotalDistance());
        $this->assertFalse($statistic->isTotalDistanceKnown());
    }

    public function testTotalDurationDefaultsToNull(): void
    {
        $statistic = $this->createStatistic();

        $this->assertNull($statistic->getTotalDuration());
        $this->assertFalse($statistic->isTotalDurationKnown());
    }

    public function testSetAndGetTotalDuration(): void
    {
        $statistic = $this->createStatistic();

        $statistic->setTotalDuration(3600.0);

        $this->assertEqualsWithDelta(3600.0, $statistic->getTotalDuration(), PHP_FLOAT_EPSILON);
        $this->assertTrue($statistic->isTotalDurationKnown());
    }

    public function testSetTotalDurationToNullMarksItAsUnknown(): void
    {
        $statistic = $this->createStatistic();

        $statistic->setTotalDuration(3600.0);
        $statistic->setTotalDuration(null);

        $this->assertNull($statistic->getTotalDuration());
        $this->assertFalse($statistic->isTotalDurationKnown());
    }

    #[DataProvider('provideNumberOfActivitiesWithDistanceScenarios')]
    public function testAreMostActivitiesWithDistanceUsesCountWhenKnown(
        int $total,
        int $withDistance,
        bool $expected
    ): void {
        $statistic = $this->createStatistic();
        $statistic->setNumberOfActivities($total, $withDistance);

        $this->assertSame($expected, $statistic->areMostActivitiesWithDistance());
    }

    public function testAreMostActivitiesWithDistanceFallsBackToTotalDistanceWhenCountUnknown(): void
    {
        $statistic = $this->createStatistic();
        $statistic->setNumberOfActivities(10); // no second argument -> null
        $statistic->setTotalDistance(5.0);

        $this->assertTrue($statistic->areMostActivitiesWithDistance());
    }

    public function testAreMostActivitiesWithDistanceIsFalseWhenNeitherCountNorDistanceIsKnown(): void
    {
        $statistic = $this->createStatistic();
        $statistic->setNumberOfActivities(10);

        $this->assertFalse($statistic->areMostActivitiesWithDistance());
    }

    public function testAreMostActivitiesWithDistanceIsFalseWhenTotalDistanceIsExactlyZero(): void
    {
        $statistic = $this->createStatistic();
        $statistic->setNumberOfActivities(10);
        $statistic->setTotalDistance(0.0);

        $this->assertFalse($statistic->areMostActivitiesWithDistance());
    }

    public function testAreMostActivitiesWithDistanceIsTrueForPositiveTotalDistance(): void
    {
        $statistic = $this->createStatistic();
        $statistic->setNumberOfActivities(10);
        $statistic->setTotalDistance(0.001);

        $this->assertTrue($statistic->areMostActivitiesWithDistance());
    }

    public function testCountBranchTakesPrecedenceOverDistanceWhenCountIsZero(): void
    {
        $statistic = $this->createStatistic();
        $statistic->setNumberOfActivities(10, 0);
        $statistic->setTotalDistance(999.0);

        $this->assertFalse($statistic->areMostActivitiesWithDistance());
    }

    /**
     * @return \Iterator<string, array<(bool|int)>>
     */
    public static function provideNumberOfActivitiesWithDistanceScenarios(): \Iterator
    {
        yield 'exactly half counts as "most" (>=)' => [10, 5, true];
        yield 'more than half' => [10, 8, true];
        yield 'less than half' => [10, 3, false];
        yield 'all activities have distance' => [10, 10, true];
        yield 'none of the activities have distance' => [10, 0, false];
        yield 'single activity, with distance' => [1, 1, true];
        yield 'single activity, without distance' => [1, 0, false];
    }
}
