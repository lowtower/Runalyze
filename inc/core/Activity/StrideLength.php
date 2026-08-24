<?php

/**
 * This file contains class::StrideLength
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Stride length
 *
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class StrideLength implements ValueInterface
{
    /**
     * Value [cm]
     * @var float
     */
    protected $Centimeter;

    /**
     * @var \Runalyze\Parameter\Application\DistanceUnitSystem
     */
    protected $UnitSystem;

    /**
     * Format stride length
     * @param int $centimeter
     * @param bool $withUnit
     */
    public static function format($centimeter, $withUnit = true): string
    {
        return (new self($centimeter))->string($withUnit);
    }

    /**
     * @param int $centimeter
     */
    public function __construct($centimeter = 0, ?DistanceUnitSystem $unitSystem = null)
    {
        $this->Centimeter = $centimeter;
        $this->UnitSystem = ($unitSystem instanceof \Runalyze\Parameter\Application\DistanceUnitSystem) ? $unitSystem : Configuration::General()->distanceUnitSystem();
    }

    /**
     * Label for stride length
     */
    public function label(): string
    {
        return __('Stride length');
    }

    public function unit(): string
    {
        if ($this->UnitSystem->isImperial()) {
            return DistanceUnitSystem::FEET;
        }

        return DistanceUnitSystem::METER;
    }

    /**
     * Set stride length
     * @param int $centimeter
     * @return \Runalyze\Activity\StrideLength $this-reference
     */
    public function set($centimeter): self
    {
        $this->Centimeter = $centimeter;

        return $this;
    }

    /**
     * @param float $meter
     * @return \Runalyze\Activity\StrideLength $this-reference
     */
    public function setMeter($meter): self
    {
        $this->Centimeter = $meter * 100;

        return $this;
    }

    /**
     * @param int $feet
     * @return \Runalyze\Activity\StrideLength $this-reference
     */
    public function setFeet($feet): self
    {
        $this->Centimeter = $feet / DistanceUnitSystem::FEET_MULTIPLIER * 1000 * 100;

        return $this;
    }

    /**
     * @param int $strideLength [mixed unit]
     * @return \Runalyze\Activity\StrideLength $this-reference
     */
    public function setInPreferredUnit($strideLength): self
    {
        if ($this->UnitSystem->isImperial()) {
            $this->setFeet($strideLength);
        } else {
            $this->setMeter($strideLength);
        }

        return $this;
    }

    /**
     * Get stride length
     * @return int [cm]
     */
    public function value(): float
    {
        return round($this->Centimeter);
    }

    public function cm(): float
    {
        return round($this->Centimeter);
    }

    public function meter(): float
    {
        return round($this->Centimeter / 100, 2);
    }

    public function feet(): float
    {
        return round($this->Centimeter * DistanceUnitSystem::FEET_MULTIPLIER / 1000 / 100, 2);
    }

    /**
     * @return int [mixed unit]
     */
    public function valueInPreferredUnit(): float
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->feet();
        }

        return $this->meter();
    }

    /**
     * Format value as string
     * @param bool $withUnit
     */
    public function string($withUnit = true): string
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->stringFeet($withUnit);
        }

        return $this->stringMeter($withUnit);
    }

    /**
     * @param bool $withUnit
     */
    public function stringMeter($withUnit = true): string
    {
        return number_format($this->Centimeter / 100, 2) . ($withUnit ? '&nbsp;' . DistanceUnitSystem::METER : '');
    }

    /**
     * @param bool $withUnit
     */
    public function stringCM($withUnit = true): string
    {
        return number_format($this->Centimeter, 0) . ($withUnit ? '&nbsp;' . DistanceUnitSystem::CM : '');
    }

    /**
     * @param bool $withUnit
     */
    public function stringFeet($withUnit = true): string
    {
        return number_format($this->feet(), 1, '.', '') . ($withUnit ? '&nbsp;' . DistanceUnitSystem::FEET : '');
    }
}
