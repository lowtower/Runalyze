<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryValue;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FlightRatio;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\FlightTime;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Pace;

final class CustomExpressionQueryValuesTest extends TestCase
{
    public function testFlightTimeBuildsExpectedExpressionAndBothNotNullChecks(): void
    {
        $andWhereCalls = [];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('(30000/tr.cadence - tr.groundcontact) as ft')
            ->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')
            ->willReturnCallback(
                function (string $condition) use (&$andWhereCalls, $queryBuilder): QueryBuilder {
                    $andWhereCalls[] = $condition;
                    return $queryBuilder;
                }
            );

        new FlightTime()->addSelectionToQuery($queryBuilder, 'tr', 'ft');

        $this->assertSame(
            ['tr.cadence IS NOT NULL', 'tr.groundcontact IS NOT NULL'],
            $andWhereCalls
        );
    }

    public function testFlightRatioBuildsExpectedExpressionAndBothNotNullChecks(): void
    {
        $andWhereCalls = [];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('(1 - tr.cadence * tr.groundcontact / 30000) as fr')
            ->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')
            ->willReturnCallback(
                function (string $condition) use (&$andWhereCalls, $queryBuilder): QueryBuilder {
                    $andWhereCalls[] = $condition;
                    return $queryBuilder;
                }
            );

        new FlightRatio()->addSelectionToQuery($queryBuilder, 'tr', 'fr');

        $this->assertSame(
            ['tr.cadence IS NOT NULL', 'tr.groundcontact IS NOT NULL'],
            $andWhereCalls
        );
    }

    public function testPaceBuildsExpectedExpression(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('tr.s/tr.distance as p')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('tr.distance IS NOT NULL')
            ->willReturn($queryBuilder);

        new Pace()->addSelectionToQuery($queryBuilder, 'tr', 'p');
    }

    public function testPaceDoesNotGuardTheDurationColumnAgainstNull(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('addSelect')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with($this->logicalNot($this->stringContains('.s IS NOT NULL')));

        new Pace()->addSelectionToQuery($queryBuilder, 'tr', 'p');
    }
}
