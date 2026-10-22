<?php

declare(strict_types=1);

namespace Runalyze\Parameter\Application;

use Doctrine\Common\Collections\ArrayCollection;
use Runalyze\Bundle\CoreBundle\Entity\Common\IdentifiableEntityInterface;
use Runalyze\Bundle\CoreBundle\Entity\Common\NamedEntityInterface;

final readonly class DatabaseOrderTestNamedFixture implements NamedEntityInterface
{
    public function __construct(
        private string $name
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}

final readonly class DatabaseOrderTestIdentifiableFixture implements IdentifiableEntityInterface
{
    public function __construct(
        private int $id
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}

#[\PHPUnit\Framework\Attributes\Group('requiresSqlite')]
final class DatabaseOrderTest extends \PHPUnit\Framework\TestCase
{
    protected DatabaseOrder $object;

    protected function setUp(): void
    {
        $this->object = new DatabaseOrder();
    }

    public function testAsQuery(): void
    {
        $this->object->set(DatabaseOrder::ASC);
        $this->assertSame('ORDER BY `id` ASC', $this->object->asQuery());

        $this->object->set(DatabaseOrder::DESC);
        $this->assertSame('ORDER BY `id` DESC', $this->object->asQuery());

        $this->object->set(DatabaseOrder::ALPHA);
        $this->assertSame('ORDER BY `name` ASC', $this->object->asQuery());
    }

    public function testUnknownValueFallsBackToDefault(): void
    {
        $this->object->set(DatabaseOrder::DESC);
        $this->object->set('unknown');

        $this->assertSame(DatabaseOrder::ASC, $this->object->value());
    }

    public function testAscendingOrdering(): void
    {
        $array = [
            '3' => [
                'id' => 3,
            ],
            '5' => [
                'id' => 5,
            ],
            '2' => [
                'id' => 2,
            ],
            '1' => [
                'id' => 1,
            ],
            '4' => [
                'id' => 4,
            ],
        ];

        $this->object->set(DatabaseOrder::ASC);
        $this->object->sort($array);

        $this->assertEquals([
            '1' => [
                'id' => 1,
            ],
            '2' => [
                'id' => 2,
            ],
            '3' => [
                'id' => 3,
            ],
            '4' => [
                'id' => 4,
            ],
            '5' => [
                'id' => 5,
            ],
        ], $array);
    }

    public function testDescendingOrdering(): void
    {
        $array = [
            '3' => [
                'id' => 3,
            ],
            '5' => [
                'id' => 5,
            ],
            '2' => [
                'id' => 2,
            ],
            '1' => [
                'id' => 1,
            ],
            '4' => [
                'id' => 4,
            ],
        ];

        $this->object->set(DatabaseOrder::DESC);
        $this->object->sort($array);

        $this->assertEquals([
            '5' => [
                'id' => 5,
            ],
            '4' => [
                'id' => 4,
            ],
            '3' => [
                'id' => 3,
            ],
            '2' => [
                'id' => 2,
            ],
            '1' => [
                'id' => 1,
            ],
        ], $array);
    }

    public function testAlphabeticalOrdering(): void
    {
        $array = [
            'f' => [
                'name' => 'Foo',
            ],
            'v' => [
                'name' => 'Value',
            ],
            't' => [
                'name' => 'Test',
            ],
            'b' => [
                'name' => 'Bar',
            ],
            'n' => [
                'name' => 'Name',
            ],
        ];

        $this->object->set(DatabaseOrder::ALPHA);
        $this->object->sort($array);

        $this->assertEquals([
            'b' => [
                'name' => 'Bar',
            ],
            'f' => [
                'name' => 'Foo',
            ],
            'n' => [
                'name' => 'Name',
            ],
            't' => [
                'name' => 'Test',
            ],
            'v' => [
                'name' => 'Value',
            ],
        ], $array);
    }

    public function testSortCollectionOrdersByNameAlphabetically(): void
    {
        $collection = new ArrayCollection([
            new DatabaseOrderTestNamedFixture('Charlie'),
            new DatabaseOrderTestNamedFixture('Alice'),
            new DatabaseOrderTestNamedFixture('Bob'),
        ]);

        $this->object->set(DatabaseOrder::ALPHA);
        $sorted = $this->object->sortCollection($collection);

        $this->assertSame(['Alice', 'Bob', 'Charlie'], array_values(array_map(static fn (NamedEntityInterface $entity): string => $entity->getName(), $sorted->toArray())));
    }

    public function testSortCollectionOrdersByIdAscending(): void
    {
        $collection = new ArrayCollection([
            new DatabaseOrderTestIdentifiableFixture(3),
            new DatabaseOrderTestIdentifiableFixture(1),
            new DatabaseOrderTestIdentifiableFixture(2),
        ]);

        $this->object->set(DatabaseOrder::ASC);
        $sorted = $this->object->sortCollection($collection);

        $this->assertSame([1, 2, 3], array_values(array_map(static fn (IdentifiableEntityInterface $entity): ?int => $entity->getId(), $sorted->toArray())));
    }

    public function testSortCollectionOrdersByIdDescending(): void
    {
        $collection = new ArrayCollection([
            new DatabaseOrderTestIdentifiableFixture(1),
            new DatabaseOrderTestIdentifiableFixture(3),
            new DatabaseOrderTestIdentifiableFixture(2),
        ]);

        $this->object->set(DatabaseOrder::DESC);
        $sorted = $this->object->sortCollection($collection);

        $this->assertSame([3, 2, 1], array_values(array_map(static fn (IdentifiableEntityInterface $entity): ?int => $entity->getId(), $sorted->toArray())));
    }

    public function testSortCollectionLeavesOrderUnchangedWhenEntitiesDoNotImplementRequiredInterface(): void
    {
        $collection = new ArrayCollection([
            new DatabaseOrderTestNamedFixture('Charlie'),
            new DatabaseOrderTestNamedFixture('Alice'),
            new DatabaseOrderTestNamedFixture('Bob'),
        ]);

        // ASC/DESC expect IdentifiableEntityInterface, but our fixtures only implement NamedEntityInterface.
        $this->object->set(DatabaseOrder::ASC);
        $sorted = $this->object->sortCollection($collection);

        $this->assertSame(['Charlie', 'Alice', 'Bob'], array_values(array_map(static fn (NamedEntityInterface $entity): string => $entity->getName(), $sorted->toArray())));
    }
}
