<?php

/**
 * This file contains class::PoolLength
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Pool length
 *
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class PoolLength implements ValueInterface
{
    /**
     * Value [cm]
     */
    protected float $value;

    protected int $Centimeter;

    protected DistanceUnitSystem $UnitSystem;

    public static function format(int $centimeter, bool $withUnit = true): string
    {
        return (new self($centimeter))->string($withUnit);
    }

    public function __construct(float $value = 0.0, ?DistanceUnitSystem $unitSystem = null)
    {
        $this->set($value);
        $this->UnitSystem = ($unitSystem instanceof DistanceUnitSystem) ? $unitSystem : Configuration::General()->distanceUnitSystem();
    }

    public function label(): string
    {
        return __('Pool length');
    }

    public function unit(): string
    {
        if ($this->UnitSystem->isImperial()) {
            return DistanceUnitSystem::YARDS;
        }

        return DistanceUnitSystem::METER;
    }

    /**
     * Set pool length
     * @param float $value
     * @return \Runalyze\Activity\PoolLength $this-reference
     */
    public function set($value): self
    {
        $this->value = $value;
        $this->Centimeter = (int) $value;

        return $this;
    }

    /**
     * @return \Runalyze\Activity\PoolLength $this-reference
     */
    public function setMeter(float $meter): self
    {
        $this->set($meter * 100);

        return $this;
    }

    /**
     * @return \Runalyze\Activity\PoolLength $this-reference
     */
    public function setYards(float $yards): self
    {
        $this->set($yards / DistanceUnitSystem::YARD_MULTIPLIER * 1000 * 100);

        return $this;
    }

    /**
     * @return \Runalyze\Activity\PoolLength $this-reference
     */
    public function setInPreferredUnit(float $poolLength): self
    {
        if ($this->UnitSystem->isImperial()) {
            $this->setYards($poolLength);
        } else {
            $this->setMeter($poolLength);
        }

        return $this;
    }

    /**
     * Get pool length
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

    public function yards(): float
    {
        return round($this->value * DistanceUnitSystem::YARD_MULTIPLIER / 1000 / 100, 2);
    }

    public function valueInPreferredUnit(): float
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->yards();
        }

        return $this->meter();
    }

    /**
     * Format value as string
     */
    public function string(bool $withUnit = true): string
    {
        if ($this->UnitSystem->isImperial()) {
            return $this->stringYards($withUnit);
        }

        return $this->stringMeter($withUnit);
    }

    public function stringMeter(bool $withUnit = true): string
    {
        return number_format($this->Centimeter / 100, 0) . ($withUnit ? '&nbsp;' . DistanceUnitSystem::METER : '');
    }

    public function stringCM(bool $withUnit = true): string
    {
        return number_format($this->Centimeter, 0, '', '') . ($withUnit ? '&nbsp;' . DistanceUnitSystem::CM : '');
    }

    public function stringYards(bool $withUnit = true): string
    {
        return number_format($this->yards(), 2, '.', '') . ($withUnit ? '&nbsp;' . DistanceUnitSystem::YARDS : '');
    }
}
