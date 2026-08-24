<?php

/**
 * This file contains class::Pressure
 * @package Runalyze\Data\Weather
 */

namespace Runalyze\Data\Weather;

use Runalyze\Activity\ValueInterface;

/**
 * @author Hannes Christiansen
 * @author Michael Pohl
 * @package Runalyze\Data\Weather
 */
class Pressure implements ValueInterface
{
    /**
     * @var float [hPa]
     */
    protected $Value;

    /**
     * Construct Pressure
     * @param float|null $hectoPascals [hPa]
     */
    public function __construct($hectoPascals = null)
    {
        $this->set($hectoPascals);
    }

    /**
     * Set Pressure
     * @param float|null $hectoPascals [hPa]
     * @return \Runalyze\Data\Weather\Pressure $this-reference
     * @throws \InvalidArgumentException
     */
    public function set($hectoPascals): self
    {
        if ($hectoPascals !== null && ! is_numeric($hectoPascals)) {
            throw new \InvalidArgumentException('Value must be numerical.');
        }

        $this->Value = $hectoPascals;

        return $this;
    }

    #
    /**
     * Label for value
     */
    public function label(): string
    {
        return __('Pressure');
    }

    /**
     * Label for value
     */
    public function unit(): string
    {
        return 'hpa';
    }

    /**
     * @return null|float [hPa]
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

        return round($this->Value) . ($withUnit ? '&nbsp;' . $this->unit() : '');
    }

    /**
     * Pressure is unknown?
     */
    public function isUnknown(): bool
    {
        return $this->Value === null;
    }
}
