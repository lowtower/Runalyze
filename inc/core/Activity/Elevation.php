<?php

/**
 * This file contains class::Elevation
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class Elevation implements ValueInterface
{
    protected float $value;

    /**
     * Value in m
     */
    protected int $Meter;

    protected DistanceUnitSystem $UnitSystem;

    public static function format(int $meter, bool $withUnit = true): string
    {
        return (new self($meter))->string($withUnit);
    }

    public function __construct(float $value = 0.0, ?DistanceUnitSystem $unitSystem = null)
    {
        $this->set($value);
        $this->UnitSystem = ($unitSystem instanceof \Runalyze\Parameter\Application\DistanceUnitSystem) ? $unitSystem : Configuration::General()->distanceUnitSystem();
    }

    /**
     * Label for elevation
     */
    public function label(): string
    {
        return __('Elevation');
    }

    public function unit(): string
    {
        if ($this->UnitSystem->isImperial()) {
            return DistanceUnitSystem::FEET;
        }

        return DistanceUnitSystem::METER;
    }

    /**
     * Set elevation
     * @return \Runalyze\Activity\Elevation $this-reference
     */
    public function set($value): self
    {
        $this->value = $value;
        $this->Meter = (int) $value;

        return $this;
    }

    /**
     * @return \Runalyze\Activity\Elevation $this-reference
     */
    public function setFeet(float $feet): self
    {
        $this->set($feet / DistanceUnitSystem::FEET_MULTIPLIER * 1000);

        return $this;
    }

    /**
     * @return \Runalyze\Activity\Elevation $this-reference
     */
    public function setInPreferredUnit(float $elevation): self
    {
        if ($this->UnitSystem->isImperial()) {
            $this->setFeet($elevation);
        } else {
            $this->set($elevation);
        }

        return $this;
    }

    /**
     * Get elevation
     * @return float [m]
     */
    public function value(): float
    {
        return $this->value;
    }

    public function meter(): int
    {
        return $this->Meter;
    }

    public function feet(): int
    {
        return (int) round($this->value * DistanceUnitSystem::FEET_MULTIPLIER / 1000);
    }

    public function valueInPreferredUnit(): int
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->feet();
        }

        return $this->meter();
    }

    /**
     * Format value as string
     */
    public function string(bool $withUnit = true): string
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->stringFeet($withUnit);
        }

        return $this->stringMeter($withUnit);
    }

    public function stringMeter(bool $withUnit = true): string
    {
        return number_format($this->Meter, 0, '', '.') . ($withUnit ? '&nbsp;' . DistanceUnitSystem::METER : '');
    }

    public function stringFeet(bool $withUnit = true): string
    {
        return number_format($this->feet(), 0, '', '.') . ($withUnit ? '&nbsp;' . DistanceUnitSystem::FEET : '');
    }
}
