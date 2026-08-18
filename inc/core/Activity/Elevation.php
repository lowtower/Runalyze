<?php

/**
 * This file contains class::Elevation
 * @package Runalyze\Activity
 */

namespace Runalyze\Activity;

use Runalyze\Configuration;
use Runalyze\Parameter\Application\DistanceUnitSystem;

/**
 * Elevation
 *
 * @author Hannes Christiansen
 * @package Runalyze\Activity
 */
class Elevation implements ValueInterface
{
	/**
	 * Value in m
	 * @var float [m]
	 */
	protected $Meter;

	/**
	 * @var \Runalyze\Parameter\Application\DistanceUnitSystem
	 */
	protected $UnitSystem;

    /**
     * Format elevation
     * @param int $meter
     * @param bool $withUnit
     */
    public static function format($meter, $withUnit = true): string
	{
		return (new self($meter))->string($withUnit);
	}

	/**
	 * @param int $meter
	 * @param \Runalyze\Parameter\Application\DistanceUnitSystem $unitSystem
	 */
	public function __construct($meter = 0, ?DistanceUnitSystem $unitSystem = null)
	{
		$this->Meter = $meter;
		$this->UnitSystem = ($unitSystem instanceof \Runalyze\Parameter\Application\DistanceUnitSystem) ? $unitSystem : Configuration::General()->distanceUnitSystem();
	}

    /**
     * Label for elevation
     */
    public function label(): string
	{
		return __('Elevation');
	}

    /**
     * Unit
     */
    public function unit(): string
	{
		if ($this->UnitSystem->isImperial()) {
			return DistanceUnitSystem::FEET;
		}

		return DistanceUnitSystem::METER;
	}

	/**
	 * Set elevation
	 * @param int $meter
	 * @return \Runalyze\Activity\Elevation $this-reference
	 */
	public function set($meter): self
	{
		$this->Meter = $meter;

		return $this;
	}

	/**
	 * @param int $feet
	 * @return \Runalyze\Activity\Elevation $this-reference
	 */
	public function setFeet($feet): self
	{
		$this->Meter = $feet / DistanceUnitSystem::FEET_MULTIPLIER * 1000;

		return $this;
	}

	/**
	 * @param int $elevation [mixed unit]
	 * @return \Runalyze\Activity\Elevation $this-reference
	 */
	public function setInPreferredUnit($elevation): self
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
	 * @return int [m]
	 */
	public function value(): float
	{
		return round($this->Meter);
	}

	public function meter(): float
	{
		return round($this->Meter);
	}

	public function feet(): float
	{
		return round($this->Meter * DistanceUnitSystem::FEET_MULTIPLIER / 1000);
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
		return number_format($this->Meter, 0, '', '.').($withUnit ? '&nbsp;'.DistanceUnitSystem::METER : '');
	}

    /**
     * @param bool $withUnit
     */
    public function stringFeet($withUnit = true): string
	{
		return number_format($this->feet(), 0, '', '.').($withUnit ? '&nbsp;'.DistanceUnitSystem::FEET : '');
	}
}
