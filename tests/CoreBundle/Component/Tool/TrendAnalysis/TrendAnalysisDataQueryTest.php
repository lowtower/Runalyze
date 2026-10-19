<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\TrendAnalysis;

use Doctrine\ORM\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Pace;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValueInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValues;
use Runalyze\Bundle\CoreBundle\Component\Tool\TrendAnalysis\TrendAnalysisDataQuery;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisData;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Metrics\Common\UnitInterface;

class TrendAnalysisDataQueryTest extends TestCase
{
    private function createTrendAnalysisDataMock(string $valueEnum = QueryValues::PACE): Stub&TrendAnalysisData
    {
        $stub = $this->createStub(TrendAnalysisData::class);
        $stub->method('getValueToLookAt')
            ->willReturn($valueEnum);

        return $stub;
    }

    private function getProtectedProperty(object $object, string $name): mixed
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($name);

        return $property->getValue($object);
    }

    private function setProtectedProperty(object $object, string $name, mixed $value): void
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($name);
        $property->setValue($object, $value);
    }

    public function testConstructorResolvesQueryValueFromTrendAnalysisDatasLookAtValue(): void
    {
        $query = new TrendAnalysisDataQuery($this->createTrendAnalysisDataMock(QueryValues::PACE));

        $this->assertInstanceOf(Pace::class, $this->getProtectedProperty($query, 'QueryValue'));
    }

    public function testConstructorThrowsForUnknownLookAtEnum(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TrendAnalysisDataQuery($this->createTrendAnalysisDataMock('not-a-real-value'));
    }

    public function testGetValueUnitDoesNotSetPaceUnitWhenNoSportIsSelected(): void
    {
        $trendAnalysisData = $this->createTrendAnalysisDataMock();
        $trendAnalysisData->method('getSport')
            ->willReturn([]);
        $query = new TrendAnalysisDataQuery($trendAnalysisData);

        $expectedUnit = $this->createStub(UnitInterface::class);
        $queryValueMock = $this->createStub(QueryValueInterface::class);
        $queryValueMock->method('getValueUnit')
            ->willReturn($expectedUnit);
        $this->setProtectedProperty($query, 'QueryValue', $queryValueMock);

        // Stays a mock: it has an expectation (never)
        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->never())
            ->method('setPaceUnitFromSport');

        $result = $query->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }

    public function testGetValueUnitSetsPaceUnitFromTheSingleSelectedSport(): void
    {
        $sport = $this->createStub(Sport::class);
        $trendAnalysisData = $this->createTrendAnalysisDataMock();
        $trendAnalysisData->method('getSport')
            ->willReturn([$sport]);
        $query = new TrendAnalysisDataQuery($trendAnalysisData);

        $queryValueMock = $this->createStub(QueryValueInterface::class);
        $queryValueMock->method('getValueUnit')
            ->willReturn($this->createStub(UnitInterface::class));
        $this->setProtectedProperty($query, 'QueryValue', $queryValueMock);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('setPaceUnitFromSport')
            ->with($sport);

        $query->getValueUnit($unitSystem);
    }

    public function testGetValueUnitDoesNotSetPaceUnitWhenMultipleSportsAreSelected(): void
    {
        $trendAnalysisData = $this->createTrendAnalysisDataMock();
        $trendAnalysisData->method('getSport')
            ->willReturn([
                $this->createStub(Sport::class),
                $this->createStub(Sport::class),
            ]);
        $query = new TrendAnalysisDataQuery($trendAnalysisData);

        $queryValueMock = $this->createStub(QueryValueInterface::class);
        $queryValueMock->method('getValueUnit')
            ->willReturn($this->createStub(UnitInterface::class));
        $this->setProtectedProperty($query, 'QueryValue', $queryValueMock);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->never())
            ->method('setPaceUnitFromSport');

        $query->getValueUnit($unitSystem);
    }

    private function createPartialMockWithBuildQueryMocked(TrendAnalysisData $trendAnalysisData): TrendAnalysisDataQuery&MockObject
    {
        return $this->getMockBuilder(TrendAnalysisDataQuery::class)
            ->setConstructorArgs([$trendAnalysisData])
            ->onlyMethods(['buildQuery'])
            ->getMock();
    }

    public function testGetResultsBuildsTimeKeyedMapWithConvertedValues(): void
    {
        $trendAnalysisData = $this->createTrendAnalysisDataMock();
        $trendAnalysisData->method('getSport')
            ->willReturn([]);

        $query = $this->createPartialMockWithBuildQueryMocked($trendAnalysisData);

        $unitMock = $this->createStub(UnitInterface::class);
        $unitMock->method('fromBaseUnit')
            ->willReturnCallback(static fn (float $v): float => $v * 2);

        $queryValueMock = $this->createStub(QueryValueInterface::class);
        $queryValueMock->method('getValueUnit')
            ->willReturn($unitMock);
        $this->setProtectedProperty($query, 'QueryValue', $queryValueMock);

        $doctrineQueryMock = $this->createStub(Query::class);
        $doctrineQueryMock->method('toIterable')
            ->willReturn([
                // Numeric STRING key to verify the (int) cast on 'time'.
                [
                    'time' => '1000',
                    'value' => 5.0,
                ],
                [
                    'time' => 2000,
                    'value' => 7.5,
                ],
            ]);

        // Was method() only. expects(once()) makes it a real expectation.
        $query->expects($this->once())
            ->method('buildQuery')
            ->willReturn($doctrineQueryMock);

        $result = $query->getResults(
            $this->createStub(TrainingRepository::class),
            $this->createStub(Account::class),
            $this->createStub(UnitSystem::class)
        );

        $this->assertSame([
            1000 => 10.0,
            2000 => 15.0,
        ], $result);
    }

    public function testGetResultsAccumulatesAcrossMultipleCallsInsteadOfResetting(): void
    {
        $trendAnalysisData = $this->createTrendAnalysisDataMock();
        $trendAnalysisData->method('getSport')
            ->willReturn([]);

        $query = $this->createPartialMockWithBuildQueryMocked($trendAnalysisData);

        $unitMock = $this->createStub(UnitInterface::class);
        $unitMock->method('fromBaseUnit')
            ->willReturnArgument(0);
        $queryValueMock = $this->createStub(QueryValueInterface::class);
        $queryValueMock->method('getValueUnit')
            ->willReturn($unitMock);
        $this->setProtectedProperty($query, 'QueryValue', $queryValueMock);

        $firstDoctrineQuery = $this->createStub(Query::class);
        $firstDoctrineQuery->method('toIterable')
            ->willReturn([
                [
                    'time' => 100,
                    'value' => 1.0,
                ],
            ]);
        $secondDoctrineQuery = $this->createStub(Query::class);
        $secondDoctrineQuery->method('toIterable')
            ->willReturn([
                [
                    'time' => 200,
                    'value' => 2.0,
                ],
            ]);

        // Was method() only. expects(exactly(2)) makes it a real expectation.
        $query->expects($this->exactly(2))
            ->method('buildQuery')
            ->willReturnOnConsecutiveCalls($firstDoctrineQuery, $secondDoctrineQuery);

        $trainingRepository = $this->createStub(TrainingRepository::class);
        $account = $this->createStub(Account::class);
        $unitSystem = $this->createStub(UnitSystem::class);

        $query->getResults($trainingRepository, $account, $unitSystem);
        $secondResult = $query->getResults($trainingRepository, $account, $unitSystem);

        $this->assertSame([
            100 => 1.0,
            200 => 2.0,
        ], $secondResult);
    }

    public function testGetResultsReturnsEmptyArrayWhenNoRowsAreFound(): void
    {
        $trendAnalysisData = $this->createTrendAnalysisDataMock();
        $trendAnalysisData->method('getSport')
            ->willReturn([]);

        $query = $this->createPartialMockWithBuildQueryMocked($trendAnalysisData);

        $queryValueMock = $this->createStub(QueryValueInterface::class);
        $queryValueMock->method('getValueUnit')
            ->willReturn($this->createStub(UnitInterface::class));
        $this->setProtectedProperty($query, 'QueryValue', $queryValueMock);

        $doctrineQueryMock = $this->createStub(Query::class);
        $doctrineQueryMock->method('toIterable')
            ->willReturn([]);

        // Was method() only. expects(once()) makes it a real expectation.
        $query->expects($this->once())
            ->method('buildQuery')
            ->willReturn($doctrineQueryMock);

        $result = $query->getResults(
            $this->createStub(TrainingRepository::class),
            $this->createStub(Account::class),
            $this->createStub(UnitSystem::class)
        );

        $this->assertSame([], $result);
    }
}
