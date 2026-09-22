<?php

/**
 * This file contains class::AbstractMovingAverage
 * @package Runalyze\Calculation\Math\MovingAverage
 */

namespace Runalyze\Calculation\Math\MovingAverage;

/**
 * Abstract class for moving averages
 *
 * @author Hannes Christiansen
 * @package Runalyze\Calculation\Math\MovingAverage
 */
abstract class AbstractMovingAverage implements MovingAverageInterface
{
    protected array $MovingAverage = [];

    protected int $Length = 0;

    /**
     * @param array $Data data to average
     * @param bool $indexDataIsAscending set to false if index is not ascending
     */
    public function __construct(
        protected array $Data,
        protected array $IndexData = [],
        bool $indexDataIsAscending = true
    ) {
        $this->Length = count($this->Data);

        $this->checkIndexData($indexDataIsAscending);
    }

    /**
     * @param bool $indexDataIsAscending set to false if index is not ascending
     */
    protected function checkIndexData(bool $indexDataIsAscending)
    {
        if ($this->IndexData !== []) {
            if (count($this->IndexData) !== $this->Length) {
                throw new \InvalidArgumentException('Index data must be of same length as provided data.');
            }

            if (! $indexDataIsAscending) {
                $this->makeIndexDataAscending();
            }
        }
    }

    protected function makeIndexDataAscending()
    {
        $sum = 0;
        for ($i = 0; $i < $this->Length; ++$i) {
            $sum += $this->IndexData[$i];
            $this->IndexData[$i] = $sum;
        }
    }

    /**
     * Calculate moving average
     */
    public function calculate()
    {
        if ($this->hasIndexData()) {
            $this->calculateWithIndexData();
        } else {
            $this->calculateWithoutIndexData();
        }
    }

    /**
     * Calculate if index data is there
     */
    abstract public function calculateWithIndexData();

    /**
     * Calculate if index data is not there
     */
    abstract public function calculateWithoutIndexData();

    protected function hasIndexData(): bool
    {
        return $this->IndexData !== [];
    }

    public function movingAverage(): array
    {
        return $this->MovingAverage;
    }

    /**
     * @param int $index
     * @throws \InvalidArgumentException
     */
    public function at($index): float
    {
        if (isset($this->MovingAverage[$index])) {
            return $this->MovingAverage[$index];
        }

        throw new \InvalidArgumentException('Unknown index "' . $index . '" of moving average.');
    }
}
