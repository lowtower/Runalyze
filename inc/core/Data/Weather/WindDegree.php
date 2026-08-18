<?php

/**
 * This file contains class::WindDegree
 * @package Runalyze\Data\Weather
 */

namespace Runalyze\Data\Weather;

use Runalyze\Activity\ValueInterface;

/**
 * Wind Degree
 *
 * @author Hannes Christiansen
 * @author Michael
 * @package Runalyze\Data\Weather
 */
class WindDegree implements ValueInterface
{
    /**
     * Wind Degree
     * @var float|null [°]
     */
    protected $Value;

    /**
     * Wind Degree
     * @param float|null $degrees
     */
    public function __construct($degrees = null)
    {
        $this->set($degrees);
    }

    /**
     * Set wind Degree
     * @param float|null $degrees
     * @throws \InvalidArgumentException
     */
    public function set($degrees): self
    {
        if (null !== $degrees && ! is_numeric($degrees)) {
            throw new \InvalidArgumentException('Value must be numeric.');
        }

        $this->Value = $degrees;

        return $this;
    }

    /**
     * Label for value
     */
    public function label(): string
    {
        return __('Wind Degree');
    }

    /**
     * Label for value
     */
    public function unit(): string
    {
        return '&deg;';
    }

    /**
     * Value
     * @return null|float
     */
    public function value()
    {
        return $this->Value;
    }

    /**
     * Format value as string
     * @param bool $withUnit
     */
    public function string($withUnit = true): string
    {
        if ($this->isUnknown()) {
            return '';
        }

        return round($this->Value).($withUnit ? $this->unit() : '');
    }

    /**
     * Wind Speed is unknown?
     */
    public function isUnknown(): bool
    {
        return (null === $this->Value);
    }
}
