<?php

/**
 * This file contains class::Loop
 * @package Runalyze\Model
 */

namespace Runalyze\Model;

/**
 * Loop through object
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
abstract class Loop
{
	const BOUNDARY_AT_OR_AFTER = 0;
	const BOUNDARY_AT_OR_BEFORE_IF_POSSIBLE = 1;
    const BOUNDARY_NEAREST = 2;

	/**
	 * Current index
	 * @var int
	 */
	protected $Index = 0;

	/**
	 * Last index
	 * @var int
	 */
	protected $LastIndex = 0;

	/**
	 * Step size
	 * @var int
	 */
	protected $StepSize = 1;

	/**
	 * Total size
	 * @var int
	 */
	protected $TotalSize;

	/**
	 * Object
	 * @var \Runalyze\Model\Loopable
	 */
	protected $Object;

	/**
	 * Construct
	 * @param \Runalyze\Model\Loopable $object
	 */
	public function __construct(Loopable $object)
	{
		$this->Object = $object;

		$this->countTotalSize();
		$this->reset();
	}

	/**
	 * Count total size
	 */
	protected function countTotalSize()
	{
		$this->TotalSize = $this->Object->num();
	}

	/**
	 * @return int
	 */
	public function num()
	{
		return $this->TotalSize;
	}

	/**
	 * Reset
	 *
	 * Sets the internal pointer to the beginning.
	 * This method does not change the step size.
	 */
	public function reset()
	{
		$this->Index = 0;
		$this->LastIndex = 0;
	}

	/**
	 * Set step size
	 * @param int $size
	 */
	public function setStepSize($size)
	{
		$this->StepSize = $size;
	}

	/**
	 * Move index forward
	 * @return boolean
	 */
	public function nextStep()
	{
		$this->LastIndex = $this->Index;
		$this->Index += $this->StepSize;

		if ($this->Index > $this->TotalSize - 1) {
			$this->Index = $this->TotalSize - 1;
			return false;
		}

		return true;
	}

	/**
	 * Is at the end?
	 * @return boolean
	 */
	public function isAtEnd()
	{
		return ($this->Index >= $this->TotalSize - 1);
	}

	/**
	 * Move pointer
	 * @param string $key
	 * @param float $value to move
	 * @throws \InvalidArgumentException
	 */
	public function move($key, $value)
	{
		$this->moveTo($key, $this->Index == 0 && $value > $this->current($key) ? $value : $this->current($key) + $value);
	}

	/**
	 * @return int
	 */
	public function index()
	{
		return $this->Index;
	}

	/**
	 * @return int
	 */
	public function lastIndex()
	{
		return $this->LastIndex;
	}

	/**
	 * @return int
	 */
	public function currentStepSize()
	{
		return $this->Index - $this->LastIndex;
	}

	/**
	 * Go to index
	 * @param int $index
	 */
	public function goToIndex($index)
	{
		$this->LastIndex = $this->Index;
		$this->Index = $index;
	}

	/**
	 * Go to end
	 */
	public function goToEnd()
	{
		$this->goToIndex($this->TotalSize - 1);
	}

	/**
	 * Move pointer to a target value.
	 *
	 * BOUNDARY_AT_OR_AFTER selects the first value greater than or equal
	 * to the target.
	 *
	 * BOUNDARY_AT_OR_BEFORE_IF_POSSIBLE selects the last value below the
	 * target, provided that the loop advances beyond the previous boundary.
	 *
	 * BOUNDARY_NEAREST selects the value closest to the target, provided
	 * that selecting the previous value advances the loop.
	 *
	 * @param string $key
	 * @param int|float $target
	 * @param int $boundaryMode
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function moveTo($key, $target, $boundaryMode = self::BOUNDARY_AT_OR_AFTER)
	{
		if (! $this->Object->has($key)) {
			throw new \InvalidArgumentException(sprintf('Cannot move loop: key "%s" is not available.', $key));
		}

		$this->LastIndex = $this->Index;

		while (
			! $this->isAtEnd() &&
			$this->Object->at($this->Index, $key) < $target
		) {
			$this->Index++;
		}

		$current = $this->Object->at($this->Index, $key);

		/*
		* The target lies beyond the available data. In this case the final
		* point is the closest point at or before the target and must be kept.
		*/
		if ($this->isAtEnd() && $current < $target) {
			return;
		}

		/*
		* Do not step back if that would return to the previous boundary.
		* The loop must advance by at least one index.
		*/
		if ($this->Index <= $this->LastIndex + 1) {
			return;
		}

		// An exact match is always preferred.
		if ($current == $target) {
			return;
		}

		$previous = $this->Object->at($this->Index - 1, $key);

		if (self::BOUNDARY_AT_OR_BEFORE_IF_POSSIBLE === $boundaryMode) {
			$this->Index--;
			return;
		}

		if (
			self::BOUNDARY_NEAREST === $boundaryMode &&
			$current - $target > $target - $previous
		) {
			$this->Index--;
		}
	}

	/**
	 * Current value
	 * @param string $key
	 * @return int
	 */
	public function current($key)
	{
		if ($this->Object->has($key)) {
			return $this->Object->at($this->Index, $key);
		}

		return 0;
	}

	/**
	 * Difference for current section
	 * @param string $key
	 * @return float
	 */
	public function difference($key)
	{
		if ($this->Object->has($key)) {
			if ($this->LastIndex == 0 && $this->Index == 0) {
				return $this->Object->at($this->Index, $key);
			}

			return $this->Object->at($this->Index, $key) - $this->Object->at($this->LastIndex, $key);
		}

		return 0;
	}

	/**
	 * Sum values for current section
	 * @param string $key
	 * @return float
	 */
	public function sum($key)
	{
		$sum = 0;

		if ($this->Object->has($key)) {
			$start = $this->LastIndex == 0 ? $this->LastIndex : $this->LastIndex + 1;
			for ($i = $start; $i <= $this->Index; ++$i) {
				$v = $this->Object->at($i, $key);
				if (is_numeric($v)) $sum += $v;
			}
		}

		return $sum;
	}

	/**
	 * Maximal value for current section
	 * @param string $key
	 * @return float
	 */
	public function max($key)
	{
		$max = -PHP_INT_MAX;

		if ($this->Object->has($key)) {
			$start = $this->LastIndex == 0 ? $this->LastIndex : $this->LastIndex + 1;
			for ($i = $start; $i <= $this->Index; ++$i) {
				if ($this->Object->at($i, $key) > $max) {
					$v = $this->Object->at($i, $key);
					if (is_numeric($v)) $max = $v;
				}
			}
		}

		if ($max == -PHP_INT_MAX) {
			return null;
		}

		return $max;
	}

	/**
	 * Average value for current section
	 * @param string $key
	 * @return int
	 */
	public function average($key)
	{
		if ($this->LastIndex >= $this->Index) {
			return 0;
		}

		return $this->sum($key) / ($this->Index - $this->LastIndex + (int) ($this->LastIndex == 0));
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function slice($key)
	{
		if ($this->Object->has($key)) {
			$start = $this->LastIndex == 0 ? $this->LastIndex : $this->LastIndex + 1;
			return array_slice($this->Object->get($key), $start, $this->Index - $start + 1);
		}

		return array();
	}

	/**
	 * Slice object based on last/current index
	 * @return \Runalyze\Model\Entity
	 * @throws \RuntimeException
	 */
	public function sliceObject()
	{
		if (! $this->Object instanceof \Runalyze\Model\Entity)
		{
			throw new \RuntimeException('This object cannot be sliced.');
		}

		$data = array();

		foreach ($this->Object->properties() as $key)
		{
			if ($this->Object->isArray($key))
			{
				$data[$key] = $this->slice($key);
			}
		}

		return $this->createNewObject($data);
	}

	/**
	 * @param array $data
	 * @return \Runalyze\Model\Entity
	 */
	abstract protected function createNewObject(array $data);
}
