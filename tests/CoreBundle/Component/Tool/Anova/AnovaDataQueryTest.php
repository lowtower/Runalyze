<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\AnovaDataQuery;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\Month;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\QueryGroupInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\QueryGroups;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Pace;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValueInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValues;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;
use Runalyze\Metrics\Common\UnitInterface;

#[\PHPUnit\Framework\Attributes\Group('default')]
final class AnovaDataQueryTest extends TestCase
{
    private function createAnovaDataMock(
        string $groupEnum = QueryGroups::MONTH,
        string $valueEnum = QueryValues::PACE
    ): Stub&AnovaData {
        $anovaData = $this->createStub(AnovaData::class);
        $anovaData->method('getValueToGroupBy')
            ->willReturn($groupEnum);
        $anovaData->method('getValueToLookAt')
            ->willReturn($valueEnum);

        return $anovaData;
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

    private function invokeProtectedMethod(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionObject($object);
        $refMethod = $reflection->getMethod($method);

        return $refMethod->invokeArgs($object, $args);
    }

    public function testConstructorResolvesQueryGroupFromAnovaDatasGroupByValue(): void
    {
        $query = new AnovaDataQuery($this->createAnovaDataMock(groupEnum: QueryGroups::MONTH));

        $this->assertInstanceOf(Month::class, $this->getProtectedProperty($query, 'QueryGroup'));
    }

    public function testConstructorResolvesQueryValueFromAnovaDatasLookAtValue(): void
    {
        $query = new AnovaDataQuery($this->createAnovaDataMock(valueEnum: QueryValues::PACE));

        $this->assertInstanceOf(Pace::class, $this->getProtectedProperty($query, 'QueryValue'));
    }

    public function testConstructorThrowsForUnknownGroupByEnum(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AnovaDataQuery($this->createAnovaDataMock(groupEnum: 'not-a-real-group'));
    }

    public function testLoadAllGroupsBuildsGroupsArrayWithIntCastKeysLabelsAndEmptyData(): void
    {
        $query = new AnovaDataQuery($this->createAnovaDataMock());

        $queryGroupMock = $this->createMock(QueryGroupInterface::class);
        $entityManager = $this->createStub(EntityManager::class);
        $account = $this->createStub(Account::class);
        $anovaData = $this->getProtectedProperty($query, 'AnovaData');

        $queryGroupMock->expects($this->once())
            ->method('loadAllGroups')
            ->with($entityManager, $account, $anovaData)
            ->willReturn([
                '3' => 'March',
                '7' => 'July',
            ]);

        $this->setProtectedProperty($query, 'QueryGroup', $queryGroupMock);

        $query->loadAllGroups($entityManager, $account);

        $this->assertSame(
            [
                3 => [
                    'label' => 'March',
                    'data' => [],
                ],
                7 => [
                    'label' => 'July',
                    'data' => [],
                ],
            ],
            $this->getProtectedProperty($query, 'Groups')
        );
    }

    public function testLoadAllGroupsResetsPreviouslyStoredGroups(): void
    {
        $query = new AnovaDataQuery($this->createAnovaDataMock());
        $this->setProtectedProperty($query, 'Groups', [
            99 => [
                'label' => 'Stale',
                'data' => [1.0],
            ],
        ]);

        $queryGroupStub = $this->createStub(QueryGroupInterface::class);
        $queryGroupStub->method('loadAllGroups')
            ->willReturn([
                1 => 'Fresh',
            ]);
        $this->setProtectedProperty($query, 'QueryGroup', $queryGroupStub);

        $query->loadAllGroups($this->createStub(EntityManager::class), $this->createStub(Account::class));

        $this->assertSame([
            1 => [
                'label' => 'Fresh',
                'data' => [],
            ],
        ], $this->getProtectedProperty($query, 'Groups'));
    }

    public function testGetValueUnitDoesNotSetPaceUnitWhenNoSportIsSelected(): void
    {
        $anovaData = $this->createAnovaDataMock();
        $anovaData->method('getSport')
            ->willReturn([]);
        $query = new AnovaDataQuery($anovaData);

        $expectedUnit = $this->createStub(UnitInterface::class);
        $queryValueStub = $this->createStub(QueryValueInterface::class);
        $queryValueStub->method('getValueUnit')
            ->willReturn($expectedUnit);
        $this->setProtectedProperty($query, 'QueryValue', $queryValueStub);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->never())
            ->method('setPaceUnitFromSport');

        $result = $query->getValueUnit($unitSystem);

        $this->assertSame($expectedUnit, $result);
    }

    public function testGetValueUnitSetsPaceUnitFromTheSingleSelectedSport(): void
    {
        $sport = $this->createStub(Sport::class);
        $anovaData = $this->createAnovaDataMock();
        $anovaData->method('getSport')
            ->willReturn([$sport]);
        $query = new AnovaDataQuery($anovaData);

        $queryValueStub = $this->createStub(QueryValueInterface::class);
        $queryValueStub->method('getValueUnit')
            ->willReturn($this->createStub(UnitInterface::class));
        $this->setProtectedProperty($query, 'QueryValue', $queryValueStub);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->once())
            ->method('setPaceUnitFromSport')
            ->with($sport);

        $query->getValueUnit($unitSystem);
    }

    public function testGetValueUnitDoesNotSetPaceUnitWhenMultipleSportsAreSelected(): void
    {
        $anovaData = $this->createAnovaDataMock();
        $anovaData->method('getSport')
            ->willReturn([
                $this->createStub(Sport::class),
                $this->createStub(Sport::class),
            ]);
        $query = new AnovaDataQuery($anovaData);

        $queryValueStub = $this->createStub(QueryValueInterface::class);
        $queryValueStub->method('getValueUnit')
            ->willReturn($this->createStub(UnitInterface::class));
        $this->setProtectedProperty($query, 'QueryValue', $queryValueStub);

        $unitSystem = $this->createMock(UnitSystem::class);
        $unitSystem->expects($this->never())
            ->method('setPaceUnitFromSport');

        $query->getValueUnit($unitSystem);
    }

    public function testFilterEmptyGroupsRemovesEmptyGroupsWhenShowEmptyGroupsIsFalse(): void
    {
        $query = new AnovaDataQuery($this->createAnovaDataMock());

        $queryGroupStub = $this->createStub(QueryGroupInterface::class);
        $queryGroupStub->method('showEmptyGroups')
            ->willReturn(false);
        $this->setProtectedProperty($query, 'QueryGroup', $queryGroupStub);

        $this->setProtectedProperty($query, 'Groups', [
            1 => [
                'label' => 'HasData',
                'data' => [10.0],
            ],
            2 => [
                'label' => 'Empty',
                'data' => [],
            ],
        ]);

        $this->invokeProtectedMethod($query, 'filterEmptyGroups');

        $this->assertSame(
            [
                1 => [
                    'label' => 'HasData',
                    'data' => [10.0],
                ],
            ],
            $this->getProtectedProperty($query, 'Groups')
        );
    }

    public function testFilterEmptyGroupsKeepsEmptyGroupsWhenShowEmptyGroupsIsTrue(): void
    {
        $query = new AnovaDataQuery($this->createAnovaDataMock());

        $queryGroupStub = $this->createStub(QueryGroupInterface::class);
        $queryGroupStub->method('showEmptyGroups')
            ->willReturn(true);
        $this->setProtectedProperty($query, 'QueryGroup', $queryGroupStub);

        $groups = [
            1 => [
                'label' => 'HasData',
                'data' => [10.0],
            ],
            2 => [
                'label' => 'Empty',
                'data' => [],
            ],
        ];
        $this->setProtectedProperty($query, 'Groups', $groups);

        $this->invokeProtectedMethod($query, 'filterEmptyGroups');

        $this->assertSame($groups, $this->getProtectedProperty($query, 'Groups'));
    }

    private function createPartialMockWithBuildQueryMocked(AnovaData $anovaData): AnovaDataQuery&MockObject
    {
        return $this->getMockBuilder(AnovaDataQuery::class)
            ->setConstructorArgs([$anovaData])
            ->onlyMethods(['buildQuery'])
            ->getMock();
    }

    public function testGetResultsGroupsConvertsAndFiltersRowsCorrectly(): void
    {
        $anovaData = $this->createAnovaDataMock();
        $anovaData->method('getSport')
            ->willReturn([]);

        $query = $this->createPartialMockWithBuildQueryMocked($anovaData);

        $this->setProtectedProperty($query, 'Groups', [
            1 => [
                'label' => 'GroupOne',
                'data' => [],
            ],
            2 => [
                'label' => 'GroupTwo',
                'data' => [],
            ],
            3 => [
                'label' => 'GroupThree',
                'data' => [],
            ],
        ]);

        $queryGroupStub = $this->createStub(QueryGroupInterface::class);
        $queryGroupStub->method('showEmptyGroups')
            ->willReturn(false);
        $this->setProtectedProperty($query, 'QueryGroup', $queryGroupStub);

        $unitStub = $this->createStub(UnitInterface::class);
        $unitStub->method('fromBaseUnit')
            ->willReturnCallback(static fn (float $v): float => $v * 2);

        $queryValueStub = $this->createStub(QueryValueInterface::class);
        $queryValueStub->method('getValueUnit')
            ->willReturn($unitStub);
        $this->setProtectedProperty($query, 'QueryValue', $queryValueStub);

        $trainingRepository = $this->createStub(TrainingRepository::class);
        $account = $this->createStub(Account::class);
        $unitSystem = $this->createStub(UnitSystem::class);

        $doctrineQueryMock = $this->createMock(Query::class);
        $doctrineQueryMock->expects($this->once())
            ->method('toIterable')
            ->with([], AbstractQuery::HYDRATE_ARRAY)
            ->willReturn([
                [
                    'grouping' => '1',
                    'value' => 5.0,
                ],
                [
                    'grouping' => 1,
                    'value' => 7.0,
                ],
                [
                    'grouping' => 2,
                    'value' => 10.0,
                ],
            ]);

        $query->expects($this->once())
            ->method('buildQuery')
            ->with($trainingRepository, $account)
            ->willReturn($doctrineQueryMock);

        $result = $query->getResults($trainingRepository, $account, $unitSystem);

        $this->assertSame(
            [
                [
                    'label' => 'GroupOne',
                    'data' => [10.0, 14.0],
                ],
                [
                    'label' => 'GroupTwo',
                    'data' => [20.0],
                ],
            ],
            $result
        );
    }

    public function testGetResultsKeepsEmptyGroupsWhenShowEmptyGroupsIsTrue(): void
    {
        $anovaData = $this->createAnovaDataMock();
        $anovaData->method('getSport')
            ->willReturn([]);

        $query = $this->createPartialMockWithBuildQueryMocked($anovaData);

        $this->setProtectedProperty($query, 'Groups', [
            1 => [
                'label' => 'GroupOne',
                'data' => [],
            ],
            2 => [
                'label' => 'GroupTwo (empty)',
                'data' => [],
            ],
        ]);

        $queryGroupStub = $this->createStub(QueryGroupInterface::class);
        $queryGroupStub->method('showEmptyGroups')
            ->willReturn(true);
        $this->setProtectedProperty($query, 'QueryGroup', $queryGroupStub);

        $unitStub = $this->createStub(UnitInterface::class);
        $unitStub->method('fromBaseUnit')
            ->willReturnArgument(0);
        $queryValueStub = $this->createStub(QueryValueInterface::class);
        $queryValueStub->method('getValueUnit')
            ->willReturn($unitStub);
        $this->setProtectedProperty($query, 'QueryValue', $queryValueStub);

        $doctrineQueryStub = $this->createStub(Query::class);
        $doctrineQueryStub->method('toIterable')
            ->willReturn([
                [
                    'grouping' => 1,
                    'value' => 3.0,
                ],
            ]);

        $query->expects($this->once())
            ->method('buildQuery')
            ->willReturn($doctrineQueryStub);

        $result = $query->getResults(
            $this->createStub(TrainingRepository::class),
            $this->createStub(Account::class),
            $this->createStub(UnitSystem::class)
        );

        $this->assertSame(
            [
                [
                    'label' => 'GroupOne',
                    'data' => [3.0],
                ],
                [
                    'label' => 'GroupTwo (empty)',
                    'data' => [],
                ],
            ],
            $result
        );
    }
}
