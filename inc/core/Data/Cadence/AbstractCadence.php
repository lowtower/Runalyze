<?php

/**
 * This file contains class::AbstractCadence
 * @package Runalyze\Data\Cadence
 */

namespace Runalyze\Data\Cadence;

/**
 * Abstract class for cadence
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Cadence
 */
abstract class AbstractCadence
{
    /**
     * Value
     * @param int $value
     */
    protected int $Value = 0;

    /**
     * Factor for manipulating value
     */
    protected float $Factor = 1.0;

    public function __construct(int $value = 0)
    {
        $this->Value = (int) round($this->Factor * $value);
    }

    /**
     * @return int
     */
    final public function value()
    {
        return $this->Value;
    }

    /**
     * As string
     * @codeCoverageIgnore
     */
    final public function asString(): string
    {
        return $this->Value . '&nbsp;' . $this->unitAsString();
    }

    /**
     * As string with tooltip
     * @codeCoverageIgnore
     */
    final public function asStringWithTooltip(): string
    {
        return \Ajax::tooltip($this->asString(), $this->Value . ' ' . $this->unitExplanation());
    }

    /**
     * As string with tooltip
     * @codeCoverageIgnore
     */
    final public function unitAsStringWithTooltip(): string
    {
        return \Ajax::tooltip($this->unitAsString(), $this->unitExplanation());
    }

    final public function manipulateArray(array &$array)
    {
        $array = array_map([$this, 'useFactor'], $array);
    }

    /**
     * Change value by internal factor
     */
    final public function useFactor(int $value): float
    {
        return $this->Factor * $value;
    }

    /**
     * @codeCoverageIgnore
     */
    abstract public function label(): string;

    /**
     * @codeCoverageIgnore
     */
    abstract public function unitAsString(): string;

    /**
     * Explanation for unit
     * @codeCoverageIgnore
     */
    abstract protected function unitExplanation(): string;

    /**
     * @codeCoverageIgnore
     */
    abstract public function formularUnit(): string;
}
