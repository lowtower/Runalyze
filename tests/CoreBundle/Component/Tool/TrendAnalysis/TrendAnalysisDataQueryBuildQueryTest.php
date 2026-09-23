<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\TrendAnalysis;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValueInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValues;
use Runalyze\Bundle\CoreBundle\Component\Tool\TrendAnalysis\TrendAnalysisDataQuery;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\TrainingRepository;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisData;

class TrendAnalysisDataQueryBuildQueryTest extends TestCase
{
    private function createQuery(TrendAnalysisData $trendAnalysisData): TrendAnalysisDataQuery
    {
        $fullMock = $this->createMock(TrendAnalysisData::class);
        $fullMock->method('getValueToLookAt')
            ->willReturn(QueryValues::PACE);
        $fullMock->method('getDateFromTimestamp')
            ->willReturnCallback(
                fn (): int => $trendAnalysisData->getDateFromTimestamp()
            );
        $fullMock->method('getDateToTimestamp')
            ->willReturnCallback(
                fn (): int => $trendAnalysisData->getDateToTimestamp()
            );
        $fullMock->method('getSport')
            ->willReturnCallback(
                fn (): array => $trendAnalysisData->getSport()
            );
        $fullMock->method('getType')
            ->willReturnCallback(
                fn (): array => $trendAnalysisData->getType()
            );

        return new TrendAnalysisDataQuery($fullMock);
    }

    private function setProtectedProperty(object $object, string $name, mixed $value): void
    {
        $reflection = new \ReflectionObject($object);
        $property = $reflection->getProperty($name);
        $property->setValue($object, $value);
    }

    private function invokeBuildQuery(TrendAnalysisDataQuery $query, TrainingRepository $repo, Account $account): mixed
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

        return $queryBuilder;
    }

    public function testBuildQuerySelectsTimeColumn(): void
    {
        $trendAnalysisData = $this->createMock(TrendAnalysisData::class);
        $trendAnalysisData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $trendAnalysisData->method('getDateToTimestamp')
            ->willReturn(2000);
        $trendAnalysisData->method('getSport')
            ->willReturn([]);
        $trendAnalysisData->method('getType')
            ->willReturn([]);

        $query = $this->createQuery($trendAnalysisData);
        $this->setProtectedProperty($query, 'QueryValue', $this->createMock(QueryValueInterface::class));

        $queryBuilder = $this->createFluentQueryBuilderMock();
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('t.time')
            ->willReturn($queryBuilder);
        $queryBuilder->method('expr')
            ->willReturn($this->createMock(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createMock(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createMock(Account::class));
    }

    public function testBuildQuerySetsAccountAndTimeRangeParameters(): void
    {
        $account = $this->createMock(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $trendAnalysisData = $this->createMock(TrendAnalysisData::class);
        $trendAnalysisData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $trendAnalysisData->method('getDateToTimestamp')
            ->willReturn(2000);
        $trendAnalysisData->method('getSport')
            ->willReturn([]);
        $trendAnalysisData->method('getType')
            ->willReturn([]);

        $query = $this->createQuery($trendAnalysisData);
        $this->setProtectedProperty($query, 'QueryValue', $this->createMock(QueryValueInterface::class));

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
            ->willReturn($this->createMock(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createMock(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
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

        $trendAnalysisData = $this->createMock(TrendAnalysisData::class);
        $trendAnalysisData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $trendAnalysisData->method('getDateToTimestamp')
            ->willReturn(2000);
        $trendAnalysisData->method('getSport')
            ->willReturn([$sport1, $sport2]);
        $trendAnalysisData->method('getType')
            ->willReturn([]);

        $query = $this->createQuery($trendAnalysisData);
        $this->setProtectedProperty($query, 'QueryValue', $this->createMock(QueryValueInterface::class));

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
            ->willReturn($this->createMock(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createMock(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createMock(Account::class));

        $this->assertSame([1, 2], $setParameterCalls[':sports']);
    }

    public function testBuildQueryDoesNotAddTypeConditionWhenNoTypesAreSelected(): void
    {
        $trendAnalysisData = $this->createMock(TrendAnalysisData::class);
        $trendAnalysisData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $trendAnalysisData->method('getDateToTimestamp')
            ->willReturn(2000);
        $trendAnalysisData->method('getSport')
            ->willReturn([]);
        $trendAnalysisData->method('getType')
            ->willReturn([]);

        $query = $this->createQuery($trendAnalysisData);
        $this->setProtectedProperty($query, 'QueryValue', $this->createMock(QueryValueInterface::class));

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
            ->willReturn($this->createMock(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createMock(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createMock(Account::class));

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

        $trendAnalysisData = $this->createMock(TrendAnalysisData::class);
        $trendAnalysisData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $trendAnalysisData->method('getDateToTimestamp')
            ->willReturn(2000);
        $trendAnalysisData->method('getSport')
            ->willReturn([]);
        $trendAnalysisData->method('getType')
            ->willReturn([$type1, $type2]);

        $query = $this->createQuery($trendAnalysisData);
        $this->setProtectedProperty($query, 'QueryValue', $this->createMock(QueryValueInterface::class));

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
            ->willReturn($this->createMock(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createMock(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createMock(Account::class));

        $this->assertSame([10, 11], $setParameterCalls[':types']);
    }

    public function testBuildQueryDelegatesToQueryValueWithFixedAliasAndReturnsTheBuiltQuery(): void
    {
        $trendAnalysisData = $this->createMock(TrendAnalysisData::class);
        $trendAnalysisData->method('getDateFromTimestamp')
            ->willReturn(1000);
        $trendAnalysisData->method('getDateToTimestamp')
            ->willReturn(2000);
        $trendAnalysisData->method('getSport')
            ->willReturn([]);
        $trendAnalysisData->method('getType')
            ->willReturn([]);

        $query = $this->createQuery($trendAnalysisData);

        $queryValueMock = $this->createMock(QueryValueInterface::class);
        $this->setProtectedProperty($query, 'QueryValue', $queryValueMock);

        $queryBuilder = $this->createFluentQueryBuilderMock();
        $queryBuilder->method('expr')
            ->willReturn($this->createMock(Expr::class));

        $expectedQuery = $this->createMock(Query::class);
        $queryBuilder->method('getQuery')
            ->willReturn($expectedQuery);

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryValueMock->expects($this->once())
            ->method('addSelectionToQuery')
            ->with($queryBuilder, 't', 'value');

        $result = $this->invokeBuildQuery($query, $trainingRepository, $this->createMock(Account::class));

        $this->assertSame($expectedQuery, $result);
    }
}
