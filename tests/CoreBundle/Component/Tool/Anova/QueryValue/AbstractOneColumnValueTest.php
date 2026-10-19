<?php

namespace Runalyze\Bundle\CoreBundle\Tests\Component\Tool\Anova\QueryValue;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Component\Configuration\UnitSystem;
use Runalyze\Bundle\CoreBundle\Component\Tool\Anova\QueryValue\AbstractOneColumnValue;
use Runalyze\Metrics\Common\UnitInterface;

class TestableOneColumnValue extends AbstractOneColumnValue
{
    public function __construct(
        private readonly string $Column
    ) {
    }

    protected function getColumn(): string
    {
        return $this->Column;
    }

    public function getValueUnit(UnitSystem $unitSystem): UnitInterface
    {
        throw new \LogicException('getValueUnit() is not exercised by AbstractOneColumnValueTest and should not be called.');
    }
}

final class AbstractOneColumnValueTest extends TestCase
{
    public function testAddSelectionToQueryAddsSelectWithAliasColumnAndAsName(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('tr.distance as dist')
            ->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('tr.distance IS NOT NULL')
            ->willReturn($queryBuilder);

        $value = new TestableOneColumnValue('distance');
        $value->addSelectionToQuery($queryBuilder, 'tr', 'dist');
    }

    public function testAddSelectionToQueryUsesTheGivenAliasForBothSelectAndWhere(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('addSelect')
            ->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('training.cadence as c');
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('training.cadence IS NOT NULL');

        $value = new TestableOneColumnValue('cadence');
        $value->addSelectionToQuery($queryBuilder, 'training', 'c');
    }

    public function testAsAliasDoesNotLeakIntoTheWhereClause(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('addSelect')
            ->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with($this->logicalNot($this->stringContains('as ')));

        $value = new TestableOneColumnValue('power');
        $value->addSelectionToQuery($queryBuilder, 'tr', 'some_output_alias');
    }
}
