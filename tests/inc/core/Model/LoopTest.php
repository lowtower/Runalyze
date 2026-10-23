<?php

namespace Runalyze\Model;

final class Loopable_MockTester extends Entity implements Loopable
{
    /**
     * @param array|false $data
     */
    public function __construct($data = false)
    {
        if ($data === false) {
            $data = [
                'foo' => [1, 2, 3, 4, 5],
                'bar' => [2, 4, 6, 8, 10],
            ];
        }

        parent::__construct();
        $this->Data = $data;
    }

    public function num(): int
    {
        if ($this->Data === []) {
            return 0;
        }

        $firstProperty = reset($this->Data);
        return is_array($firstProperty) ? count($firstProperty) : 0;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->Data);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function get(string $key): mixed
    {
        if (! $this->has($key)) {
            throw new \InvalidArgumentException(sprintf('Unknown data index "%s".', $key));
        }

        return $this->Data[$key];
    }

    public function isArray(string $key): bool
    {
        return $this->has($key) && is_array($this->Data[$key]);
    }

    public function properties(): array
    {
        return array_keys($this->Data);
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function at($index, $key): mixed
    {
        if (! $this->has($key) || ! array_key_exists((string) $index, $this->Data[$key])) {
            throw new \InvalidArgumentException(sprintf('Unknown data index "%s" at position %d.', $key, $index));
        }

        return $this->Data[$key][$index];
    }
}

final class Loop_MockTest extends Loop
{
    protected function createNewObject(array $data): \Runalyze\Model\Loopable_MockTester
    {
        return new Loopable_MockTester($data);
    }

    /**
     * Exposes the normal at-or-after behavior for testing.
     *
     * @param int|float $target
     */
    public function moveToAtOrAfter(string $key, $target): void
    {
        $this->moveTo($key, $target, self::BOUNDARY_AT_OR_AFTER);
    }

    /**
     * Exposes the before-or-at behavior for testing.
     *
     * @param int|float $target
     */
    public function moveToBeforeOrAt(string $key, $target): void
    {
        $this->moveTo($key, $target, self::BOUNDARY_AT_OR_BEFORE_IF_POSSIBLE);
    }

    /**
     * Exposes the nearest-boundary behavior for testing.
     *
     * @param int|float $target
     */
    public function moveToNearest(string $key, $target): void
    {
        $this->moveTo($key, $target, self::BOUNDARY_NEAREST);
    }
}

final class LoopTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param int[] $values
     */
    protected function loopWithFooValues(array $values): \Runalyze\Model\Loop_MockTest
    {
        return new Loop_MockTest(new Loopable_MockTester([
            'foo' => $values,
        ]));
    }

    protected function exampleLoop(): \Runalyze\Model\Loop_MockTest
    {
        return new Loop_MockTest(new Loopable_MockTester());
    }

    public function testSimpleLoop(): void
    {
        $Loop = $this->exampleLoop();

        $Loop->nextStep();
        $this->assertEquals(2, $Loop->current('foo'));
        $this->assertEquals(4, $Loop->current('bar'));

        $Loop->setStepSize(2);
        $Loop->nextStep();
        $this->assertEquals(4, $Loop->current('foo'));
        $this->assertEquals(8, $Loop->current('bar'));
        $this->assertEquals([3, 4], $Loop->slice('foo'));
        $this->assertEquals([6, 8], $Loop->slice('bar'));
        $this->assertEquals(3 + 4, $Loop->sum('foo'));
        $this->assertEquals(8, $Loop->max('bar'));
        $this->assertEquals(8 - 4, $Loop->difference('bar'));
        $this->assertEquals((6 + 8) / 2, $Loop->average('bar'));

        $Loop->nextStep();
        $this->assertTrue($Loop->isAtEnd());

        $Loop->reset();
        $this->assertFalse($Loop->isAtEnd());
        $this->assertEquals(1, $Loop->current('foo'));
        $this->assertEquals(2, $Loop->current('bar'));
    }

    public function testObjectSlicing(): void
    {
        $Loop = $this->exampleLoop();
        $Loop->setStepSize(2);
        $Loop->nextStep();

        $FirstSlice = $Loop->sliceObject();
        $this->assertEquals([1, 2, 3], $FirstSlice->get('foo'));
        $this->assertEquals([2, 4, 6], $FirstSlice->get('bar'));

        $Loop->goToEnd();
        $SecondSlice = $Loop->sliceObject();
        $this->assertEquals([4, 5], $SecondSlice->get('foo'));
        $this->assertEquals([8, 10], $SecondSlice->get('bar'));
    }

    public function testMoveToAtOrAfterUsesFollowingValue(): void
    {
        $Loop = $this->loopWithFooValues([0, 569, 608, 642, 680]);

        $Loop->moveToAtOrAfter('foo', 610);
        $this->assertEquals(642, $Loop->current('foo'));
    }

    public function testMoveToBeforeOrAtUsesPreviousValue(): void
    {
        $Loop = $this->loopWithFooValues([0, 569, 608, 642, 680]);

        $Loop->moveToBeforeOrAt('foo', 610);
        $this->assertEquals(608, $Loop->current('foo'));
    }

    public function testMoveToNearestUsesPreviousValueWhenCloser(): void
    {
        $Loop = $this->loopWithFooValues([0, 569, 608, 642, 680]);

        $Loop->moveToNearest('foo', 610);
        $this->assertEquals(608, $Loop->current('foo'));
    }

    public function testMoveToNearestUsesFollowingValueWhenCloser(): void
    {
        $Loop = $this->loopWithFooValues([0, 569, 608, 642, 680]);

        $Loop->moveToNearest('foo', 635);
        $this->assertEquals(642, $Loop->current('foo'));
    }

    public function testMoveToNearestKeepsExactMatch(): void
    {
        $Loop = $this->loopWithFooValues([0, 569, 608, 642, 680]);

        $Loop->moveToNearest('foo', 642);
        $this->assertEquals(642, $Loop->current('foo'));
    }

    public function testMoveToNearestUsesFollowingValueOnTie(): void
    {
        $Loop = $this->loopWithFooValues([0, 600, 620, 640]);

        $Loop->moveToNearest('foo', 610);
        $this->assertEquals(620, $Loop->current('foo'));
    }

    public function testMoveToNearestDoesNotReusePreviousBoundary(): void
    {
        $Loop = $this->loopWithFooValues([0, 569, 608, 642, 680]);

        $Loop->moveToNearest('foo', 610);
        $this->assertEquals(608, $Loop->current('foo'));

        /*
        * For target 620, 608 is still slightly closer than 642:
        *
        * 620 - 608 = 12
        * 642 - 620 = 22
        *
        * But using 608 again would create an empty section, so the loop
        * must advance to 642.
        */
        $Loop->moveToNearest('foo', 620);
        $this->assertEquals(642, $Loop->current('foo'));
    }

    public function testDifferenceAfterMoveToNearest(): void
    {
        $Loop = $this->loopWithFooValues([0, 569, 608, 642, 680]);

        $Loop->moveToNearest('foo', 610);
        $this->assertEquals(608, $Loop->difference('foo'));

        $Loop->moveToNearest('foo', 670);
        $this->assertEquals(72, $Loop->difference('foo'));
    }

    public function testMoveToNearestThrowsExceptionForMissingProperty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $Loop = $this->exampleLoop();
        $Loop->moveToNearest('missing', 10);
    }

    public function testMockSupportsCustomProperties(): void
    {
        $object = new Loopable_MockTester([
            'foo' => [0, 10, 20],
        ]);

        $this->assertTrue($object->has('foo'));
        $this->assertFalse($object->has('bar'));
        $this->assertEquals([0, 10, 20], $object->get('foo'));
        $this->assertSame(3, $object->num());
        $this->assertEquals(10, $object->at(1, 'foo'));
    }
}
