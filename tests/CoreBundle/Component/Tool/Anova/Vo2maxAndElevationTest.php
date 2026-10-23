<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryValue;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Vo2max;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\Vo2maxWithElevation;

final class Vo2maxAndElevationTest extends TestCase
{
    public function testVo2maxAddsSelectAndBothWhereClauses(): void
    {
        $andWhereCalls = [];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('tr.vo2max as v')
            ->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')
            ->willReturnCallback(
                function (string $condition) use (&$andWhereCalls, $queryBuilder): QueryBuilder {
                    $andWhereCalls[] = $condition;
                    return $queryBuilder;
                }
            );

        new Vo2max()->addSelectionToQuery($queryBuilder, 'tr', 'v');

        $this->assertSame(
            ['tr.vo2max IS NOT NULL', 'tr.useVO2max = 1'],
            $andWhereCalls
        );
    }

    public function testVo2maxWithElevationInheritsVo2maxsQueryBehaviorWithItsOwnColumn(): void
    {
        $andWhereCalls = [];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('tr.vo2maxWithElevation as v')
            ->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')
            ->willReturnCallback(
                function (string $condition) use (&$andWhereCalls, $queryBuilder): QueryBuilder {
                    $andWhereCalls[] = $condition;
                    return $queryBuilder;
                }
            );

        new Vo2maxWithElevation()->addSelectionToQuery($queryBuilder, 'tr', 'v');

        $this->assertSame(
            ['tr.vo2maxWithElevation IS NOT NULL', 'tr.useVO2max = 1'],
            $andWhereCalls
        );
    }
}
