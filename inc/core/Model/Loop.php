<?php

/**
 * This file contains class::Loop
 * @package Runalyze\Model
 */

namespace Runalyze\Model;

use League\Geotools\Geohash\Geohash;

/**
 * Loop through object
 *
 * @author Hannes Christiansen
 * @package Runalyze\Model
 */
abstract class Loop
{
    public const BOUNDARY_AT_OR_AFTER = 0;

    public const BOUNDARY_AT_OR_BEFORE_IF_POSSIBLE = 1;

    public const BOUNDARY_NEAREST = 2;

    /**
     * Current index
     */
    protected int $Index = 0;

    /**
     * Last index
     */
    protected int $LastIndex = 0;

    /**
     * Step size
     */
    protected int $StepSize = 1;

    /**
     * Total size
     */
    protected int $TotalSize;

    protected ?Loopable $Object = null;

    /**
     * Construct
     */
    public function __construct(?Loopable $object)
    {
        $this->Object = $object;

        $this->countTotalSize();
        $this->reset();
    }

    protected function countTotalSize()
    {
        $this->TotalSize = $this->Object->num();
    }

    public function num(): int
    {
        return $this->TotalSize;
    }

    /**
     * Sets the internal pointer to the beginning.
     * This method does not change the step size.
     */
    public function reset()
    {
        $this->Index = 0;
        $this->LastIndex = 0;
    }

    public function setStepSize(int $size): void
    {
        if ($size <= 0) {
            throw new \InvalidArgumentException('Step size must be greater than zero.');
        }

        $this->StepSize = $size;
    }

    /**
     * Move index forward
     */
    public function nextStep(): bool
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
     */
    public function isAtEnd(): bool
    {
        return $this->Index >= $this->TotalSize - 1;
    }

    /**
     * Move pointer
     * @param float $value to move
     * @throws \InvalidArgumentException
     */
    public function move(string $key, float $value)
    {
        $this->moveTo($key, $this->Index === 0 && $value > $this->current($key) ? $value : $this->current($key) + $value);
    }

    public function index(): int
    {
        return $this->Index;
    }

    public function lastIndex(): int
    {
        return $this->LastIndex;
    }

    public function currentStepSize(): int
    {
        return $this->Index - $this->LastIndex;
    }

    public function goToIndex(int $index)
    {
        $this->LastIndex = $this->Index;
        $this->Index = $index;
    }

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
     * @param int|float $target
     *
     * @throws \InvalidArgumentException
     */
    protected function moveTo(string $key, $target, int $boundaryMode = self::BOUNDARY_AT_OR_AFTER)
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

        if ($boundaryMode === self::BOUNDARY_AT_OR_BEFORE_IF_POSSIBLE) {
            $this->Index--;
            return;
        }

        if (
            $boundaryMode === self::BOUNDARY_NEAREST &&
            $current - $target > $target - $previous
        ) {
            $this->Index--;
        }
    }

    /**
     * Current value
     */
    public function current(string $key): int|float|string
    {
        if ($this->Object->has($key)) {
            return $this->Object->at($this->Index, $key);
        }

        return 0;
    }

    /**
     * Difference for current section
     */
    public function difference(string $key): float
    {
        if ($this->Object->has($key)) {
            if ($this->LastIndex === 0 && $this->Index === 0) {
                return $this->Object->at($this->Index, $key);
            }

            return $this->Object->at($this->Index, $key) - $this->Object->at($this->LastIndex, $key);
        }

        return 0;
    }

    /**
     * Sum values for current section
     */
    public function sum(string $key): float
    {
        $sum = 0;

        if ($this->Object->has($key)) {
            $start = $this->LastIndex === 0 ? $this->LastIndex : $this->LastIndex + 1;
            for ($i = $start; $i <= $this->Index; ++$i) {
                $v = $this->Object->at($i, $key);
                if (is_numeric($v)) {
                    $sum += $v;
                }
            }
        }

        return $sum;
    }

    /**
     * Maximal value for current section
     */
    public function max(string $key): ?float
    {
        $max = -PHP_INT_MAX;

        if ($this->Object->has($key)) {
            $start = $this->LastIndex === 0 ? $this->LastIndex : $this->LastIndex + 1;
            for ($i = $start; $i <= $this->Index; ++$i) {
                if ($this->Object->at($i, $key) > $max) {
                    $v = $this->Object->at($i, $key);
                    if (is_numeric($v)) {
                        $max = $v;
                    }
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
     */
    public function average(string $key): ?float
    {
        if ($this->LastIndex >= $this->Index) {
            return null;
        }

        return $this->sum($key) / ($this->Index - $this->LastIndex + (int) ($this->LastIndex === 0));
    }

    public function slice(string $key): array
    {
        if ($this->Object->has($key)) {
            $start = $this->LastIndex === 0 ? $this->LastIndex : $this->LastIndex + 1;
            return array_slice($this->Object->get($key), $start, $this->Index - $start + 1);
        }

        return [];
    }

    /**
     * Slice object based on last/current index
     * @throws \RuntimeException
     */
    public function sliceObject(): \Runalyze\Model\Entity
    {
        if (! $this->Object instanceof \Runalyze\Model\Entity) {
            throw new \RuntimeException('This object cannot be sliced.');
        }

        $data = [];

        foreach ($this->Object->properties() as $key) {
            if ($this->Object->isArray($key)) {
                $data[$key] = $this->slice($key);
            }
        }

        return $this->createNewObject($data);
    }

    /**
     * @param mixed[][] $data
     */
    abstract protected function createNewObject(array $data): \Runalyze\Model\Entity;

    protected function decodedGeohash(string $geohash): Geohash
    {
        /** @var Geohash $decoded */
        $decoded = (new Geohash())->decode($geohash);

        return $decoded;
    }
}
