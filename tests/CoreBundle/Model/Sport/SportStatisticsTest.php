<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Model\Sport;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Model\Sport\SportStatistic;
use Runalyze\Bundle\CoreBundle\Model\Sport\SportStatistics;

class SportStatisticsTest extends TestCase
{
    private function createSport(int $id): Sport&\PHPUnit\Framework\MockObject\MockObject
    {
        $sport = $this->createMock(Sport::class);
        $sport->method('getId')
            ->willReturn($id);

        return $sport;
    }

    /**
     * @return array<int|string, \Runalyze\Bundle\CoreBundle\Entity\Sport|int|float|null>
     */
    private function createRow(Sport $sport, int $num, ?int $countDistance, ?float $distance, ?float $timeInS): array
    {
        return [
            0 => $sport,
            'num' => $num,
            'count_distance' => $countDistance,
            'distance' => $distance,
            'time_in_s' => $timeInS,
        ];
    }

    public function testGetStartDateReturnsConstructorValue(): void
    {
        $startDate = new \DateTime('2024-01-01');

        $statistics = new SportStatistics($startDate, []);

        $this->assertSame($startDate, $statistics->getStartDate());
    }

    public function testEmptyQueryResultProducesEmptyStatistics(): void
    {
        $statistics = new SportStatistics(new \DateTime(), []);

        $this->assertSame([], $statistics->getStatistics());
        $this->assertSame(0, $statistics->getCount());
    }

    public function testSingleRowIsConvertedToSportStatistic(): void
    {
        $sport = $this->createSport(3);
        $row = $this->createRow($sport, 12, 8, 55.5, 7200.0);

        $statistics = new SportStatistics(new \DateTime(), [$row]);
        $result = $statistics->getStatistics();

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('3', $result);
        $this->assertSame($sport, $result['3']->getSport());
        $this->assertSame(12, $result['3']->getNumberOfActivities());
        $this->assertSame(55.5, $result['3']->getTotalDistance());
        $this->assertSame(7200.0, $result['3']->getTotalDuration());
    }

    public function testGetCountMatchesNumberOfRows(): void
    {
        $rows = [
            $this->createRow($this->createSport(1), 1, 1, 1.0, 1.0),
            $this->createRow($this->createSport(2), 2, 2, 2.0, 2.0),
        ];

        $statistics = new SportStatistics(new \DateTime(), $rows);

        $this->assertSame(2, $statistics->getCount());
    }

    public function testHasStatisticForReturnsTrueForKnownSport(): void
    {
        $sport = $this->createSport(5);
        $row = $this->createRow($sport, 3, 3, 10.0, 1000.0);

        $statistics = new SportStatistics(new \DateTime(), [$row]);

        $this->assertTrue($statistics->hasStatisticFor($sport));
    }

    public function testGetStatisticForReturnsTheStoredStatisticForKnownSport(): void
    {
        $sport = $this->createSport(5);
        $row = $this->createRow($sport, 3, 3, 10.0, 1000.0);

        $statistics = new SportStatistics(new \DateTime(), [$row]);
        $result = $statistics->getStatisticFor($sport);

        $this->assertSame($sport, $result->getSport());
        $this->assertSame(3, $result->getNumberOfActivities());
    }

    public function testHasStatisticForReturnsFalseForUnknownSport(): void
    {
        $knownSport = $this->createSport(1);
        $unknownSport = $this->createSport(999);
        $row = $this->createRow($knownSport, 1, 1, 1.0, 1.0);

        $statistics = new SportStatistics(new \DateTime(), [$row]);

        $this->assertFalse($statistics->hasStatisticFor($unknownSport));
    }

    public function testGetStatisticForReturnsFreshEmptyStatisticForUnknownSport(): void
    {
        $knownSport = $this->createSport(1);
        $unknownSport = $this->createSport(999);
        $row = $this->createRow($knownSport, 1, 1, 1.0, 1.0);

        $statistics = new SportStatistics(new \DateTime(), [$row]);
        $result = $statistics->getStatisticFor($unknownSport);

        $this->assertInstanceOf(SportStatistic::class, $result);
        $this->assertSame($unknownSport, $result->getSport());
        $this->assertNull($result->getNumberOfActivities());
        $this->assertNull($result->getTotalDistance());
        $this->assertNull($result->getTotalDuration());
    }

    public function testGetStatisticForUnknownSportReturnsANewInstanceEachTime(): void
    {
        $unknownSport = $this->createSport(999);

        $statistics = new SportStatistics(new \DateTime(), []);

        $first = $statistics->getStatisticFor($unknownSport);
        $second = $statistics->getStatisticFor($unknownSport);

        $this->assertNotSame($first, $second);
        $this->assertSame(0, $statistics->getCount());
    }

    public function testMultipleSportsAreIndexedIndependently(): void
    {
        $sportA = $this->createSport(1);
        $sportB = $this->createSport(2);

        $rows = [
            $this->createRow($sportA, 5, 5, 20.0, 2000.0),
            $this->createRow($sportB, 7, 7, 30.0, 3000.0),
        ];

        $statistics = new SportStatistics(new \DateTime(), $rows);

        $this->assertTrue($statistics->hasStatisticFor($sportA));
        $this->assertTrue($statistics->hasStatisticFor($sportB));
        $this->assertSame(5, $statistics->getStatisticFor($sportA)->getNumberOfActivities());
        $this->assertSame(7, $statistics->getStatisticFor($sportB)->getNumberOfActivities());
    }

    public function testDuplicateRowsForSameSportIdOverwriteEachOther(): void
    {
        $sport = $this->createSport(1);

        $rows = [
            $this->createRow($sport, 5, 5, 20.0, 2000.0),
            $this->createRow($sport, 99, 99, 999.0, 99999.0),
        ];

        $statistics = new SportStatistics(new \DateTime(), $rows);

        $this->assertSame(1, $statistics->getCount());
        $this->assertSame(99, $statistics->getStatisticFor($sport)->getNumberOfActivities());
    }
}
