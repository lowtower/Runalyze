<?php

/**
 * This file contains class::StrideLength
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class StrideLength implements ValueInterface
{
    /**
     * Value [cm]
     */
    protected float $value;

    protected int $Centimeter;

    protected DistanceUnitSystem $UnitSystem;

    public static function format(int $centimeter, bool $withUnit = true): string
    {
        return new self($centimeter)->string($withUnit);
    }

    public function __construct(float $value = 0.0, ?DistanceUnitSystem $unitSystem = null)
    {
        $this->set($value);
        $this->UnitSystem = $unitSystem ?? Configuration::General()->distanceUnitSystem();
    }

    /**
     * @codeCoverageIgnore
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

    public function set(mixed $value): self
    {
        $this->value = (float) $value;
        $this->Centimeter = (int) round((float) $value);

        return $this;
    }

    public function setMeter(float $meter): self
    {
        $this->set($meter * 100);

        return $this;
    }

    public function setFeet(float $feet): self
    {
        $this->set($feet / DistanceUnitSystem::FEET_MULTIPLIER * 1000 * 100);

        return $this;
    }

    /**
     * @param float $strideLength [mixed unit]
     */
    public function setInPreferredUnit(float $strideLength): self
    {
        if ($this->UnitSystem->isImperial()) {
            $this->setFeet($strideLength);
        } else {
            $this->setMeter($strideLength);
        }

        return $this;
    }

    /**
     * @return float [cm]
     */
    public function value(): float
    {
        return $this->value;
    }

    public function cm(): int
    {
        return $this->Centimeter;
    }

    public function centimeter(): int
    {
        return $this->Centimeter;
    }

    public function meter(): float
    {
        return round($this->value / 100, 2);
    }

    public function feet(): float
    {
        return round($this->value * DistanceUnitSystem::FEET_MULTIPLIER / 1000 / 100, 2);
    }

    public function valueInPreferredUnit(): float
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->feet();
        }

        return $this->meter();
    }

    public function string(bool $withUnit = true): string
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->stringFeet($withUnit);
        }

        return $this->stringMeter($withUnit);
    }

    public function stringMeter(bool $withUnit = true): string
    {
        return number_format($this->Centimeter / 100, 2) . ($withUnit ? '&nbsp;' . DistanceUnitSystem::METER : '');
    }

    public function stringCM(bool $withUnit = true): string
    {
        return number_format($this->Centimeter, 0) . ($withUnit ? '&nbsp;' . DistanceUnitSystem::CM : '');
    }

    public function stringFeet(bool $withUnit = true): string
    {
        return number_format($this->feet(), 1, '.', '') . ($withUnit ? '&nbsp;' . DistanceUnitSystem::FEET : '');
    }
}
