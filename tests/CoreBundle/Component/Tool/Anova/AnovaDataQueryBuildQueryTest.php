<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\AnovaDataQuery;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\QueryGroupInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryGroup\QueryGroups;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValueInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValues;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\Tools\Anova\AnovaData;

class AnovaDataQueryBuildQueryTest extends TestCase
{
    private function createAnovaDataQuery(AnovaData $anovaData): AnovaDataQuery
    {
        $anovaDataFull = $this->createMock(AnovaData::class);
        $anovaDataFull->method('getValueToGroupBy')
            ->willReturn(QueryGroups::MONTH);
        $anovaDataFull->method('getValueToLookAt')
            ->willReturn(QueryValues::PACE);

        $anovaDataFull->method('getDateFromTimestamp')
            ->willReturnCallback(
                fn (): int => $anovaData->getDateFromTimestamp()
            );
        $anovaDataFull->method('getDateToTimestamp')
            ->willReturnCallback(
                fn (): int => $anovaData->getDateToTimestamp()
            );
        $anovaDataFull->method('getSport')
            ->willReturnCallback(
                fn (): array => $anovaData->getSport()
            );
        $anovaDataFull->method('getType')
            ->willReturnCallback(
                fn (): array => $anovaData->getType()
            );

        return new AnovaDataQuery($anovaDataFull);
    }

    private function setProtectedProperty(object $object, string $name, mixed $value): void
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($name);
        $property->setValue($object, $value);
    }

    private function invokeBuildQuery(AnovaDataQuery $query, TrainingRepository $repo, Account $account): mixed
    {
        $reflection = new \ReflectionObject($query);
        $method = $reflection->getMethod('buildQuery');

        return $method->invoke($query, $repo, $account);
    }

    private function createFluentQueryBuilderMock(): QueryBuilder&\PHPUnit\Framework\MockObject\MockObject
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('select')
            ->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')
            ->willReturn($queryBuilder);
        $queryBuilder->method('join')
            ->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')
            ->willReturn($queryBuilder);
        $queryBuilder->method('distinct')
            ->willReturn($queryBuilder);

        return $queryBuilder;
    }

    public function testBuildQuerySetsAccountAndTimeRangeParameters(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $anovaData->method('getDateToTimestamp')
            ->willReturn(2000);
        $anovaData->method('getSport')
            ->willReturn([]);
        $anovaData->method('getType')
            ->willReturn([]);

        $query = $this->createAnovaDataQuery($anovaData);
        $this->setProtectedProperty($query, 'QueryGroup', $this->createStub(QueryGroupInterface::class));
        $this->setProtectedProperty($query, 'QueryValue', $this->createStub(QueryValueInterface::class));

        $setParameterCalls = [];
        $queryBuilder = $this->createFluentQueryBuilderMock();
        $queryBuilder->method('setParameter')
            ->willReturnCallback(
                function (string $name, $value) use (&$setParameterCalls, $queryBuilder): QueryBuilder {
                    $setParameterCalls[$name] = $value;

                    return $queryBuilder;
                }
            );
        $queryBuilder->method('expr')
            ->willReturn($this->createStub(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createStub(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $account);

        $this->assertSame(42, $setParameterCalls['account']);
        $this->assertSame(1000, $setParameterCalls['startTime']);
        $this->assertSame(2000, $setParameterCalls['endTime']);
        $this->assertSame([], $setParameterCalls[':sports']);
    }

    public function testBuildQueryMapsSelectedSportsToTheirIdsForTheInClause(): void
    {
        $sport1 = $this->createMock(Sport::class);
        $sport1->method('getId')
            ->willReturn(1);
        $sport2 = $this->createMock(Sport::class);
        $sport2->method('getId')
            ->willReturn(2);

        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $anovaData->method('getDateToTimestamp')
            ->willReturn(2000);
        $anovaData->method('getSport')
            ->willReturn([$sport1, $sport2]);
        $anovaData->method('getType')
            ->willReturn([]);

        $query = $this->createAnovaDataQuery($anovaData);
        $this->setProtectedProperty($query, 'QueryGroup', $this->createStub(QueryGroupInterface::class));
        $this->setProtectedProperty($query, 'QueryValue', $this->createStub(QueryValueInterface::class));

        $setParameterCalls = [];
        $queryBuilder = $this->createFluentQueryBuilderMock();
        $queryBuilder->method('setParameter')
            ->willReturnCallback(
                function (string $name, $value) use (&$setParameterCalls, $queryBuilder): QueryBuilder {
                    $setParameterCalls[$name] = $value;

                    return $queryBuilder;
                }
            );
        $queryBuilder->method('expr')
            ->willReturn($this->createStub(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createStub(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createStub(Account::class));

        $this->assertSame([1, 2], $setParameterCalls[':sports']);
    }

    public function testBuildQueryDoesNotAddTypeConditionWhenNoTypesAreSelected(): void
    {
        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $anovaData->method('getDateToTimestamp')
            ->willReturn(2000);
        $anovaData->method('getSport')
            ->willReturn([]);
        $anovaData->method('getType')
            ->willReturn([]);

        $query = $this->createAnovaDataQuery($anovaData);
        $this->setProtectedProperty($query, 'QueryGroup', $this->createStub(QueryGroupInterface::class));
        $this->setProtectedProperty($query, 'QueryValue', $this->createStub(QueryValueInterface::class));

        $setParameterCalls = [];
        $queryBuilder = $this->createFluentQueryBuilderMock();
        $queryBuilder->method('setParameter')
            ->willReturnCallback(
                function (string $name, $value) use (&$setParameterCalls, $queryBuilder): QueryBuilder {
                    $setParameterCalls[$name] = $value;

                    return $queryBuilder;
                }
            );
        $queryBuilder->method('expr')
            ->willReturn($this->createStub(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createStub(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createStub(Account::class));

        $this->assertArrayNotHasKey(':types', $setParameterCalls);
    }

    public function testBuildQueryMapsSelectedTypesToTheirIdsForTheInClauseWhenPresent(): void
    {
        $type1 = $this->createMock(Type::class);
        $type1->method('getId')
            ->willReturn(10);
        $type2 = $this->createMock(Type::class);
        $type2->method('getId')
            ->willReturn(11);

        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $anovaData->method('getDateToTimestamp')
            ->willReturn(2000);
        $anovaData->method('getSport')
            ->willReturn([]);
        $anovaData->method('getType')
            ->willReturn([$type1, $type2]);

        $query = $this->createAnovaDataQuery($anovaData);
        $this->setProtectedProperty($query, 'QueryGroup', $this->createStub(QueryGroupInterface::class));
        $this->setProtectedProperty($query, 'QueryValue', $this->createStub(QueryValueInterface::class));

        $setParameterCalls = [];
        $queryBuilder = $this->createFluentQueryBuilderMock();
        $queryBuilder->method('setParameter')
            ->willReturnCallback(
                function (string $name, $value) use (&$setParameterCalls, $queryBuilder): QueryBuilder {
                    $setParameterCalls[$name] = $value;

                    return $queryBuilder;
                }
            );
        $queryBuilder->method('expr')
            ->willReturn($this->createStub(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createStub(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createStub(Account::class));

        $this->assertSame([10, 11], $setParameterCalls[':types']);
    }

    public function testBuildQueryDelegatesToQueryGroupAndQueryValueWithFixedAliasesAndReturnsTheBuiltQuery(): void
    {
        $anovaData = $this->createMock(AnovaData::class);
        $anovaData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $anovaData->method('getDateToTimestamp')
            ->willReturn(2000);
        $anovaData->method('getSport')
            ->willReturn([]);
        $anovaData->method('getType')
            ->willReturn([]);

        $query = $this->createAnovaDataQuery($anovaData);

        $queryGroupMock = $this->createMock(QueryGroupInterface::class);
        $queryValueMock = $this->createMock(QueryValueInterface::class);
        $this->setProtectedProperty($query, 'QueryGroup', $queryGroupMock);
        $this->setProtectedProperty($query, 'QueryValue', $queryValueMock);

        $queryBuilder = $this->createFluentQueryBuilderMock();
        $queryBuilder->method('expr')
            ->willReturn($this->createStub(Expr::class));

        $expectedQuery = $this->createStub(Query::class);
        $queryBuilder->method('getQuery')
            ->willReturn($expectedQuery);

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryGroupMock->expects($this->once())
            ->method('addSelectionToQuery')
            ->with($queryBuilder, 't', 'grouping', 's');
        $queryValueMock->expects($this->once())
            ->method('addSelectionToQuery')
            ->with($queryBuilder, 't', 'value');

        $result = $this->invokeBuildQuery($query, $trainingRepository, $this->createStub(Account::class));

        $this->assertSame($expectedQuery, $result);
    }
}
