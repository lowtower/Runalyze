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
    protected ?float $Value = null;

    /**
     * Wind Degree
     */
    public function __construct(?float $degrees = null)
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
        if ($degrees !== null && ! is_numeric($degrees)) {
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

    public function unit(): string
    {
        return '&deg;';
    }

    public function value(): ?float
    {
        return $this->Value;
    }

    /**
     * Format value as string
     */
    public function string(bool $withUnit = true): string
    {
        if ($this->isUnknown()) {
            return '';
        }

        return round($this->Value) . ($withUnit ? $this->unit() : '');
    }

    /**
     * Wind Speed is unknown?
     */
    public function isUnknown(): bool
    {
        return $this->Value === null;
    }
}
