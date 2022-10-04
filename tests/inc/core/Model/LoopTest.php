<?php

namespace Runalyze\Model;

class Loopable_MockTester extends Entity implements Loopable {
    /**
     * @param array|false $data
     */
    public function __construct($data = false)
	{
        if (false === $data) {
            $data = array(
                'foo' => array(1, 2, 3, 4, 5),
                'bar' => array(2, 4, 6, 8, 10)
            );
        }
		parent::__construct(array());
		$this->Data = $data;
    }

    /**
     * @return int
     */
    public function num()
    {
        if (empty($this->Data)) {
            return 0;
        }
        $firstProperty = reset($this->Data);
        return is_array($firstProperty) ? count($firstProperty) : 0;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->Data);
    }

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf('Unknown data index "%s".', $key));
        }
        return $this->Data[$key];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isArray($key)
    {
        return $this->has($key) && is_array($this->Data[$key]);
    }

    /**
     * @return array
     */
    public function properties()
    {
        return array_keys($this->Data);
    }

    /**
     * @param int $index
     * @param string $key
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function at($index, $key)
    {
        if (!$this->has($key) || !array_key_exists($index, $this->Data[$key])) {
            throw new \InvalidArgumentException(sprintf('Unknown data index "%s" at position %d.', $key, $index));
        }
        return $this->Data[$key][$index];
    }
}

class Loop_MockTest extends Loop
{
    protected function createNewObject(array $data)
    {
        return new Loopable_MockTester($data);
    }

    /**
     * Exposes the normal at-or-after behavior for testing.
     *
     * @param string $key
     * @param int|float $target
     */
    public function moveToAtOrAfter($key, $target)
    {
        $this->moveTo($key, $target, self::BOUNDARY_AT_OR_AFTER);
    }

    /**
     * Exposes the before-or-at behavior for testing.
     *
     * @param string $key
     * @param int|float $target
     */
    public function moveToBeforeOrAt($key, $target)
    {
        $this->moveTo($key, $target, self::BOUNDARY_AT_OR_BEFORE_IF_POSSIBLE);
    }

    /**
     * Exposes the nearest-boundary behavior for testing.
     *
     * @param string $key
     * @param int|float $target
     */
    public function moveToNearest($key, $target)
    {
        $this->moveTo($key, $target, self::BOUNDARY_NEAREST);
    }
}

class LoopTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @param array $values
	 *
	 * @return Loop_MockTest
	 */
	protected function loopWithFooValues(array $values)
	{
		return new Loop_MockTest(new Loopable_MockTester(array('foo' => $values)));
	}

	protected function exampleLoop() {
		return new Loop_MockTest(new Loopable_MockTester());
	}

	public function testSimpleLoop() {
		$Loop = $this->exampleLoop();

		$Loop->nextStep();
		$this->assertEquals(2, $Loop->current('foo'));
		$this->assertEquals(4, $Loop->current('bar'));

		$Loop->setStepSize(2);
		$Loop->nextStep();
		$this->assertEquals(4, $Loop->current('foo'));
		$this->assertEquals(8, $Loop->current('bar'));
		$this->assertEquals(array(3,4), $Loop->slice('foo'));
		$this->assertEquals(array(6,8), $Loop->slice('bar'));
		$this->assertEquals(3+4, $Loop->sum('foo'));
		$this->assertEquals(8, $Loop->max('bar'));
		$this->assertEquals(8-4, $Loop->difference('bar'));
		$this->assertEquals((6+8)/2, $Loop->average('bar'));

		$Loop->nextStep();
		$this->assertTrue($Loop->isAtEnd());

		$Loop->reset();
		$this->assertFalse($Loop->isAtEnd());
		$this->assertEquals(1, $Loop->current('foo'));
		$this->assertEquals(2, $Loop->current('bar'));
	}

	public function testObjectSlicing() {
		$Loop = $this->exampleLoop();
		$Loop->setStepSize(2);
		$Loop->nextStep();

		$FirstSlice = $Loop->sliceObject();
		$this->assertEquals(array(1, 2, 3), $FirstSlice->get('foo'));
		$this->assertEquals(array(2, 4, 6), $FirstSlice->get('bar'));

		$Loop->goToEnd();
		$SecondSlice = $Loop->sliceObject();
		$this->assertEquals(array(4, 5), $SecondSlice->get('foo'));
		$this->assertEquals(array(8, 10), $SecondSlice->get('bar'));
	}

	public function testMoveToAtOrAfterUsesFollowingValue()
	{
		$Loop = $this->loopWithFooValues(array(0, 569, 608, 642, 680));

		$Loop->moveToAtOrAfter('foo', 610);
		$this->assertEquals(642, $Loop->current('foo'));
	}

	public function testMoveToBeforeOrAtUsesPreviousValue()
	{
		$Loop = $this->loopWithFooValues(array(0, 569, 608, 642, 680));

		$Loop->moveToBeforeOrAt('foo', 610);
		$this->assertEquals(608, $Loop->current('foo'));
	}

	public function testMoveToNearestUsesPreviousValueWhenCloser()
	{
		$Loop = $this->loopWithFooValues(array(0, 569, 608, 642, 680));

		$Loop->moveToNearest('foo', 610);
		$this->assertEquals(608, $Loop->current('foo'));
	}

	public function testMoveToNearestUsesFollowingValueWhenCloser()
	{
		$Loop = $this->loopWithFooValues(array(0, 569, 608, 642, 680));

		$Loop->moveToNearest('foo', 635);
		$this->assertEquals(642, $Loop->current('foo'));
	}

	public function testMoveToNearestKeepsExactMatch()
	{
		$Loop = $this->loopWithFooValues(array(0, 569, 608, 642, 680));

		$Loop->moveToNearest('foo', 642);
		$this->assertEquals(642, $Loop->current('foo'));
	}

	public function testMoveToNearestUsesFollowingValueOnTie()
	{
		$Loop = $this->loopWithFooValues(array(0, 600, 620, 640));

		$Loop->moveToNearest('foo', 610);
		$this->assertEquals(620, $Loop->current('foo'));
	}

	public function testMoveToNearestDoesNotReusePreviousBoundary()
	{
		$Loop = $this->loopWithFooValues(array(0, 569, 608, 642, 680));

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

	public function testDifferenceAfterMoveToNearest()
	{
		$Loop = $this->loopWithFooValues(array(0, 569, 608, 642, 680));

		$Loop->moveToNearest('foo', 610);
		$this->assertEquals(608, $Loop->difference('foo'));

		$Loop->moveToNearest('foo', 670);
		$this->assertEquals(72, $Loop->difference('foo'));
	}

	public function testMoveToNearestThrowsExceptionForMissingProperty()
	{
        $this->expectException(\InvalidArgumentException::class);
		$Loop = $this->exampleLoop();
		$Loop->moveToNearest('missing', 10);
	}

	public function testMockSupportsCustomProperties()
	{
		$object = new Loopable_MockTester(array('foo' => array(0, 10, 20)));

		$this->assertTrue($object->has('foo'));
		$this->assertFalse($object->has('bar'));
		$this->assertEquals(array(0, 10, 20), $object->get('foo'));
		$this->assertEquals(3, $object->num());
		$this->assertEquals(10, $object->at(1, 'foo'));
	}
}
