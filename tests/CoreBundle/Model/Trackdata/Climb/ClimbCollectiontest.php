<?php

declare(strict_types=1);

namespace Runalyze\Bundle\CoreBundle\Tests\Model\Trackdata\Climb;

use PHPUnit\Framework\TestCase;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\Climb;
use Runalyze\Bundle\CoreBundle\Model\Trackdata\Climb\ClimbCollection;

final class ClimbCollectionTest extends TestCase
{
    private function createClimb(): Climb
    {
        return $this->createStub(Climb::class);
    }

    public function testDefaultConstructorProducesEmptyCollection(): void
    {
        $collection = new ClimbCollection();

        $this->assertTrue($collection->isEmpty());
        $this->assertCount(0, $collection);
        $this->assertSame([], $collection->getElements());
    }

    public function testConstructorAcceptsInitialElements(): void
    {
        $climb1 = $this->createClimb();
        $climb2 = $this->createClimb();

        $collection = new ClimbCollection([$climb1, $climb2]);

        $this->assertFalse($collection->isEmpty());
        $this->assertCount(2, $collection);
        $this->assertSame([$climb1, $climb2], $collection->getElements());
    }

    public function testAddAppendsClimbToCollection(): void
    {
        $collection = new ClimbCollection();
        $climb = $this->createClimb();

        $collection->add($climb);

        $this->assertSame([$climb], $collection->getElements());
    }

    public function testAddAppendsMultipleClimbsInOrder(): void
    {
        $collection = new ClimbCollection();
        $first = $this->createClimb();
        $second = $this->createClimb();

        $collection->add($first);
        $collection->add($second);

        $this->assertSame([$first, $second], $collection->getElements());
    }

    public function testIsEmptyBecomesFalseAfterAdding(): void
    {
        $collection = new ClimbCollection();
        $this->assertTrue($collection->isEmpty());

        $collection->add($this->createClimb());

        $this->assertFalse($collection->isEmpty());
    }

    public function testCountReflectsNumberOfElements(): void
    {
        $collection = new ClimbCollection();

        $collection->add($this->createClimb());
        $collection->add($this->createClimb());
        $collection->add($this->createClimb());

        $this->assertCount(3, $collection);
    }

    public function testCountableInterfaceWorksWithNativeCountFunction(): void
    {
        $collection = new ClimbCollection([$this->createClimb(), $this->createClimb()]);

        $this->assertCount(2, $collection);
    }

    public function testOffsetExistsReturnsTrueForExistingIndex(): void
    {
        $collection = new ClimbCollection([$this->createClimb()]);

        $this->assertTrue(isset($collection[0]));
    }

    public function testOffsetExistsReturnsFalseForMissingIndex(): void
    {
        $collection = new ClimbCollection();

        $this->assertFalse(isset($collection[0]));
    }

    public function testOffsetGetReturnsElementAtIndex(): void
    {
        $climb = $this->createClimb();
        $collection = new ClimbCollection([$climb]);

        $this->assertSame($climb, $collection[0]);
    }

    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testOffsetGetForMissingIndexTriggersWarning(): void
    {
        $collection = new ClimbCollection();

        $this->expectWarning();

        $collection[0];
    }

    public function testOffsetSetStoresClimbAtGivenIndex(): void
    {
        $collection = new ClimbCollection();
        $climb = $this->createClimb();

        $collection[5] = $climb;

        $this->assertSame($climb, $collection[5]);
    }

    public function testOffsetSetWithNullOffsetAppendsClimb(): void
    {
        $collection = new ClimbCollection();
        $climb = $this->createClimb();

        $collection[] = $climb;

        $this->assertSame([$climb], $collection->getElements());
    }

    public function testOffsetSetThrowsForNonClimbValue(): void
    {
        $collection = new ClimbCollection();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Climb collection does only accept instances of Climb as elements.');

        $collection[0] = 'not-a-climb';
    }

    public function testOffsetSetThrowsForNullValue(): void
    {
        $collection = new ClimbCollection();

        $this->expectException(\InvalidArgumentException::class);

        $collection[0] = null;
    }

    public function testCollectionIsUnchangedAfterRejectedOffsetSet(): void
    {
        $collection = new ClimbCollection();

        try {
            $collection[0] = 'not-a-climb';
        } catch (\InvalidArgumentException) {
            // expected, ignored here - assertions follow below
        }

        $this->assertTrue($collection->isEmpty());
        $this->assertFalse(isset($collection[0]));
    }

    public function testOffsetUnsetRemovesElementAtIndex(): void
    {
        $collection = new ClimbCollection([$this->createClimb()]);

        unset($collection[0]);

        $this->assertFalse(isset($collection[0]));
        $this->assertTrue($collection->isEmpty());
    }

    public function testOffsetUnsetOnMissingIndexDoesNotThrow(): void
    {
        $collection = new ClimbCollection();

        unset($collection[0]);

        $this->assertTrue($collection->isEmpty());
    }

    public function testOffsetUnsetDoesNotReindexRemainingElements(): void
    {
        $first = $this->createClimb();
        $second = $this->createClimb();
        $collection = new ClimbCollection([$first, $second]);

        unset($collection[0]);

        $this->assertFalse(isset($collection[0]));
        $this->assertTrue(isset($collection[1]));
        $this->assertSame($second, $collection[1]);
    }
}
