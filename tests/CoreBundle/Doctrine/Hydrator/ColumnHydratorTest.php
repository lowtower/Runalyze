<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Doctrine\Hydrator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Doctrine\Hydrator\ColumnHydrator;

#[\PHPUnit\Framework\Attributes\Group('requiresSqlite')]
class ColumnHydratorTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
    }

    private function createHydrator(): ColumnHydrator
    {
        return new ColumnHydrator($this->createStub(EntityManagerInterface::class));
    }

    public function testHydrateAllReturnsTheFirstColumnOfEveryRow(): void
    {
        $result = $this->connection->executeQuery(
            "SELECT 1 AS id, 'a' AS name UNION ALL SELECT 2, 'b' UNION ALL SELECT 3, 'c'"
        );

        $this->assertEquals([1, 2, 3], $this->createHydrator()->hydrateAll($result, new ResultSetMapping()));
    }

    public function testHydrateAllIgnoresAllOtherColumns(): void
    {
        $result = $this->connection->executeQuery("SELECT 'first' AS a, 'second' AS b");

        $this->assertSame(['first'], $this->createHydrator()->hydrateAll($result, new ResultSetMapping()));
    }

    public function testHydrateAllReturnsAnEmptyArrayForAnEmptyResult(): void
    {
        $result = $this->connection->executeQuery('SELECT 1 WHERE 1 = 0');

        $this->assertSame([], $this->createHydrator()->hydrateAll($result, new ResultSetMapping()));
    }

    public function testTheSameHydratorCanBeUsedForSeveralResults(): void
    {
        $hydrator = $this->createHydrator();

        $first = $hydrator->hydrateAll($this->connection->executeQuery('SELECT 1'), new ResultSetMapping());
        $second = $hydrator->hydrateAll($this->connection->executeQuery('SELECT 2'), new ResultSetMapping());

        $this->assertEquals([1], $first);
        $this->assertEquals([2], $second);
    }
}
