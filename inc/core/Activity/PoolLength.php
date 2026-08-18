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
	 * @var float
	 */
	protected $Centimeter;

	/**
	 * @var \Runalyze\Parameter\Application\DistanceUnitSystem
	 */
	protected $UnitSystem;

    /**
     * Format pool length
     * @param int $centimeter
     * @param bool $withUnit
     */
    public static function format($centimeter, $withUnit = true): string
	{
		return (new self($centimeter))->string($withUnit);
	}

	/**
	 * @param int $centimeter
	 * @param \Runalyze\Parameter\Application\DistanceUnitSystem $unitSystem
	 */
	public function __construct($centimeter = 0, ?DistanceUnitSystem $unitSystem = null)
	{
		$this->Centimeter = $centimeter;
		$this->UnitSystem = ($unitSystem instanceof \Runalyze\Parameter\Application\DistanceUnitSystem) ? $unitSystem : Configuration::General()->distanceUnitSystem();
	}

    /**
     * Label for pool length
     */
    public function label(): string
	{
		return __('Pool length');
	}

    /**
     * Unit
     */
    public function unit(): string
	{
		if ($this->UnitSystem->isImperial()) {
			return DistanceUnitSystem::YARDS;
		}

		return DistanceUnitSystem::METER;
	}

	/**
	 * Set pool length
	 * @param int $centimeter
	 * @return \Runalyze\Activity\PoolLength $this-reference
	 */
	public function set($centimeter): self
	{
		$this->Centimeter = $centimeter;

		return $this;
	}

	/**
	 * @param float $meter
	 * @return \Runalyze\Activity\PoolLength $this-reference
	 */
	public function setMeter($meter): self
	{
		$this->Centimeter = $meter * 100;

		return $this;
	}

	/**
	 * @param float $yards
	 * @return \Runalyze\Activity\PoolLength $this-reference
	 */
	public function setYards($yards): self
	{
		$this->Centimeter = $yards / DistanceUnitSystem::YARD_MULTIPLIER * 1000 * 100;

		return $this;
	}

	/**
	 * @param int $poolLength [mixed unit]
	 * @return \Runalyze\Activity\PoolLength $this-reference
	 */
	public function setInPreferredUnit($poolLength): self
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

	public function yards(): float
	{
		return round($this->Centimeter * DistanceUnitSystem::YARD_MULTIPLIER / 1000 / 100, 2);
	}

	/**
	 * @return mixed [mixed unit]
	 */
	public function valueInPreferredUnit(): float
	{
		if ($this->UnitSystem->isImperial()) {
			return $this->yards();
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
			return $this->stringYards($withUnit);
		}

		return $this->stringMeter($withUnit);
	}

    /**
     * @param bool $withUnit
     */
    public function stringMeter($withUnit = true): string
	{
		return number_format($this->Centimeter/100, 0).($withUnit ? '&nbsp;'.DistanceUnitSystem::METER : '');
	}

    /**
     * @param bool $withUnit
     */
    public function stringCM($withUnit = true): string
	{
		return number_format($this->Centimeter, 0, '', '').($withUnit ? '&nbsp;'.DistanceUnitSystem::CM : '');
	}

    /**
     * @param bool $withUnit
     */
    public function stringYards($withUnit = true): string
	{
		return number_format($this->yards(), 2, '.', '').($withUnit ? '&nbsp;'.DistanceUnitSystem::YARDS : '');
	}
}
