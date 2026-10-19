<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\TrendAnalysis;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValueInterface;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\QueryValues;
use Runalyze\Bundle\CoreBundle\Component\Tool\TrendAnalysis\TrendAnalysisDataQuery;
use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Sport;
use Runalyze\Bundle\CoreBundle\Entity\Type;
use Runalyze\Bundle\CoreBundle\Form\Tools\TrendAnalysis\TrendAnalysisData;
use Runalyze\Bundle\CoreBundle\Repository\TrainingRepository;

class TrendAnalysisDataQueryBuildQueryTest extends TestCase
{
    private function createQuery(TrendAnalysisData $trendAnalysisData): TrendAnalysisDataQuery
    {
        $fullStub = $this->createStub(TrendAnalysisData::class);
        $fullStub->method('getValueToLookAt')
            ->willReturn(QueryValues::PACE);
        $fullStub->method('getDateFromTimestamp')
            ->willReturnCallback(
                fn (): int => $trendAnalysisData->getDateFromTimestamp()
            );
        $fullStub->method('getDateToTimestamp')
            ->willReturnCallback(
                fn (): int => $trendAnalysisData->getDateToTimestamp()
            );
        $fullStub->method('getSport')
            ->willReturnCallback(
                fn (): array => $trendAnalysisData->getSport()
            );
        $fullStub->method('getType')
            ->willReturnCallback(
                fn (): array => $trendAnalysisData->getType()
            );

        return new TrendAnalysisDataQuery($fullStub);
    }

    /**
     * @param array<int, Sport> $sports
     * @param array<int, Type> $types
     */
    private function createTrendAnalysisDataStub(array $sports = [], array $types = []): Stub&TrendAnalysisData
    {
        $stub = $this->createStub(TrendAnalysisData::class);
        $stub->method('getDateFromTimestamp')
            ->willReturn(1000);
        $stub->method('getDateToTimestamp')
            ->willReturn(2000);
        $stub->method('getSport')
            ->willReturn($sports);
        $stub->method('getType')
            ->willReturn($types);

        return $stub;
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

    /**
     * Works for stubs and for mocks (a MockObject is also a Stub).
     */
    private function configureFluentQueryBuilder(QueryBuilder&Stub $queryBuilder): void
    {
        $queryBuilder->method('select')
            ->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')
            ->willReturn($queryBuilder);
        $queryBuilder->method('join')
            ->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')
            ->willReturn($queryBuilder);
    }

    private function createFluentQueryBuilderStub(): QueryBuilder&Stub
    {
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $this->configureFluentQueryBuilder($queryBuilder);

        return $queryBuilder;
    }

    public function testBuildQuerySelectsTimeColumn(): void
    {
        $query = $this->createQuery($this->createTrendAnalysisDataStub());
        $this->setProtectedProperty($query, 'QueryValue', $this->createStub(QueryValueInterface::class));

        // Stays a mock: it has expectations (once, with)
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->configureFluentQueryBuilder($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('t.time')
            ->willReturn($queryBuilder);
        $queryBuilder->method('expr')
            ->willReturn($this->createStub(Expr::class));
        $queryBuilder->method('getQuery')
            ->willReturn($this->createStub(Query::class));

        $trainingRepository = $this->createMock(TrainingRepository::class);
        $trainingRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('t')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createStub(Account::class));
    }

    public function testBuildQuerySetsAccountAndTimeRangeParameters(): void
    {
        $account = $this->createStub(Account::class);
        $account->method('getId')
            ->willReturn(42);

        $query = $this->createQuery($this->createTrendAnalysisDataStub());
        $this->setProtectedProperty($query, 'QueryValue', $this->createStub(QueryValueInterface::class));

        $setParameterCalls = [];
        $queryBuilder = $this->createFluentQueryBuilderStub();
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

        $trainingRepository = $this->createStub(TrainingRepository::class);
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
        $sport1 = $this->createStub(Sport::class);
        $sport1->method('getId')
            ->willReturn(1);
        $sport2 = $this->createStub(Sport::class);
        $sport2->method('getId')
            ->willReturn(2);

        $query = $this->createQuery($this->createTrendAnalysisDataStub([$sport1, $sport2]));
        $this->setProtectedProperty($query, 'QueryValue', $this->createStub(QueryValueInterface::class));

        $setParameterCalls = [];
        $queryBuilder = $this->createFluentQueryBuilderStub();
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

        $trainingRepository = $this->createStub(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createStub(Account::class));

        $this->assertSame([1, 2], $setParameterCalls[':sports']);
    }

    public function testBuildQueryDoesNotAddTypeConditionWhenNoTypesAreSelected(): void
    {
        $query = $this->createQuery($this->createTrendAnalysisDataStub());
        $this->setProtectedProperty($query, 'QueryValue', $this->createStub(QueryValueInterface::class));

        $setParameterCalls = [];
        $queryBuilder = $this->createFluentQueryBuilderStub();
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

        $trainingRepository = $this->createStub(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createStub(Account::class));

        $this->assertArrayNotHasKey(':types', $setParameterCalls);
    }

    public function testBuildQueryMapsSelectedTypesToTheirIdsForTheInClauseWhenPresent(): void
    {
        $type1 = $this->createStub(Type::class);
        $type1->method('getId')
            ->willReturn(10);
        $type2 = $this->createStub(Type::class);
        $type2->method('getId')
            ->willReturn(11);

        $query = $this->createQuery($this->createTrendAnalysisDataStub([], [$type1, $type2]));
        $this->setProtectedProperty($query, 'QueryValue', $this->createStub(QueryValueInterface::class));

        $setParameterCalls = [];
        $queryBuilder = $this->createFluentQueryBuilderStub();
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

        $trainingRepository = $this->createStub(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->invokeBuildQuery($query, $trainingRepository, $this->createStub(Account::class));

        $this->assertSame([10, 11], $setParameterCalls[':types']);
    }

    public function testBuildQueryDelegatesToQueryValueWithFixedAliasAndReturnsTheBuiltQuery(): void
    {
        $query = $this->createQuery($this->createTrendAnalysisDataStub());

        // Stays a mock: it has an expectation (once)
        $queryValueMock = $this->createMock(QueryValueInterface::class);
        $this->setProtectedProperty($query, 'QueryValue', $queryValueMock);

        $queryBuilder = $this->createFluentQueryBuilderStub();
        $queryBuilder->method('expr')
            ->willReturn($this->createStub(Expr::class));

        $expectedQuery = $this->createStub(Query::class);
        $queryBuilder->method('getQuery')
            ->willReturn($expectedQuery);

        $trainingRepository = $this->createStub(TrainingRepository::class);
        $trainingRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryValueMock->expects($this->once())
            ->method('addSelectionToQuery')
            ->with($queryBuilder, 't', 'value');

        $result = $this->invokeBuildQuery($query, $trainingRepository, $this->createStub(Account::class));

        $this->assertSame($expectedQuery, $result);
    }
}
