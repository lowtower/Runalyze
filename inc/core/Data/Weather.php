<?php

/**
 * This file contains class::Weather
 * @package Runalyze\Data
 */

namespace Runalyze\Data;

use Runalyze\Data\Weather\Condition;
use Runalyze\Data\Weather\Humidity;
use Runalyze\Data\Weather\Pressure;
use Runalyze\Data\Weather\Sources;
use Runalyze\Data\Weather\Temperature;
use Runalyze\Data\Weather\WindDegree;
use Runalyze\Data\Weather\WindSpeed;
use Runalyze\Profile\Weather\WeatherConditionProfile;

/**
 * @author Hannes Christiansen
 * @package Runalyze\Data
 */
class Weather
{
	/**
	 * Temperature
	 * @var \Runalyze\Data\Weather\Temperature
	 */
	protected $Temperature;

	/**
	 * Condition
	 * @var \Runalyze\Data\Weather\Condition
	 */
	protected $Condition;

	/**
	 * WindSpeed
	 * @var \Runalyze\Data\Weather\WindSpeed
	 */
	protected $WindSpeed;

	/**
	 * WindDegree
	 * @var \Runalyze\Data\Weather\WindDegree
	 */
	protected $WindDegree;

	/**
	 * Humidity
	 * @var \Runalyze\Data\Weather\Humidity
	 */
	protected $Humidity;

	/**
	 * Pressure
	 * @var \Runalyze\Data\Weather\Pressure
	 */
	protected $Pressure;

	/**
	 * Enum for weather source
	 * @see \Runalyze\Data\Weather\Sources
	 * @var null|int
	 */
	protected $SourceId;

    public function __construct(Temperature $Temperature, Condition $Condition, WindSpeed $WindSpeed, WindDegree $WindDegree, Humidity $Humidity, Pressure $Pressure) {
		$this->Temperature = $Temperature;
		$this->Condition = $Condition;
		$this->WindSpeed = $WindSpeed;
		$this->WindDegree = $WindDegree;
		$this->Humidity = $Humidity;
		$this->Pressure = $Pressure;
	}

	/**
	 * Clone object
	 */
	public function __clone() {
		$this->Temperature = clone $this->Temperature;
		$this->Condition = clone $this->Condition;
		$this->WindSpeed = clone $this->WindSpeed;
		$this->WindDegree = clone $this->WindDegree;
		$this->Humidity = clone $this->Humidity;
		$this->Pressure = clone $this->Pressure;
	}

	/**
	 * @param null|int $sourceId
	 * @throws \InvalidArgumentException
	 */
	public function setSource($sourceId) {
		if ($sourceId !== null) {
			$sourceId = (int) $sourceId;

			if (! Sources::isValidValue($sourceId)) {
				throw new \InvalidArgumentException('Provided source must be null or defined in sources enum.');
			}
		}

		$this->SourceId = $sourceId;
	}

	/**
	 * @return \Runalyze\Data\Weather\Temperature
	 */
	public function temperature() {
		return $this->Temperature;
	}

	/**
	 * @return \Runalyze\Data\Weather\Condition
	 */
	public function condition() {
		return $this->Condition;
	}

	/**
	 * @return \Runalyze\Data\Weather\WindSpeed
	 */
	public function windSpeed() {
		return $this->WindSpeed;
	}

	/**
	 * @return \Runalyze\Data\Weather\WindDegree
	 */
	public function windDegree() {
		return $this->WindDegree;
	}

	/**
	 * @return \Runalyze\Data\Weather\Humidity
	 */
	public function humidity() {
		return $this->Humidity;
	}

	/**
	 * @return \Runalyze\Data\Weather\Pressure
	 */
	public function pressure() {
		return $this->Pressure;
	}

	/**
	 * @see \Runalyze\Data\Weather\Sources
	 * @return int|null
	 */
	public function source() {
		if ($this->isEmpty()) {
			$this->SourceId = null;
		}

		return $this->SourceId;
	}

	public function sourceAsString(): string {
		if ($this->sourceIsKnown()) {
			return Sources::stringFor($this->SourceId);
		}

		return '';
	}

	public function sourceIsKnown(): bool {
		return $this->SourceId !== null;
	}

    /**
     * Complete string for the weather conditions with icon, name and temperature.
     * @param bool $isNight
     */
    public function fullString($isNight = false): string {
		$icon = $this->Condition->icon();

		if ($isNight) {
			$icon->setAsNight();
		}

		return $icon->code() . ' ' . $this->Condition->string() . ' ' . __('at') . ' ' . $this->Temperature->asString();
	}

    /**
     * Is the weather-data empty?
     */
    public function isEmpty(): bool {
		return 
			$this->Temperature->isUnknown() &&
			$this->Condition->isUnknown() &&
			$this->WindSpeed->isUnknown() &&
			$this->WindDegree->isUnknown() &&
			$this->Humidity->isUnknown() &&
			$this->Pressure->isUnknown()
		;
	}

	/**
	 * Clear weather
	 */
	public function clear() {
		$this->Temperature->setTemperature(null);
		$this->Condition->set(WeatherConditionProfile::UNKNOWN);
		$this->WindSpeed->set(null);
		$this->WindDegree->set(null);
		$this->Humidity->set(null);
		$this->Pressure->set(null);
	}
}
