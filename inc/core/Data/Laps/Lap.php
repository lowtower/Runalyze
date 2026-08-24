<?php

/**
 * This file contains class::Lap
 * @package Runalyze\Data\Laps
 */

namespace Runalyze\Data\Laps;

use Runalyze\Activity\Distance;
use Runalyze\Activity\Duration;
use Runalyze\Activity\HeartRate;
use Runalyze\Activity\LapIntensity;
use Runalyze\Activity\Pace;
use Runalyze\Context;

/**
 * A single lap
 *
 * @author Hannes Christiansen
 * @package Runalyze\Data\Laps
 */
class Lap
{
	/**
	 * @var \Runalyze\Activity\Duration
	 */
	protected $LapDuration;

	/**
	 * @var \Runalyze\Activity\Distance
	 */
	protected $LapDistance;

	/**
	 * @var \Runalyze\Activity\Pace
	 */
	protected $LapPace;

	/**
	 * #TSC Mode is the "old" name and i dont want to change it.
	 * @var LapIntensity enum
	 */
	protected $Mode;

	/**
	 * @var \Runalyze\Activity\Duration
	 */
	protected $TrackDurationAtEnd;

	/**
	 * @var \Runalyze\Activity\Distance
	 */
	protected $TrackDistanceAtEnd;

	/**
	 * @var \Runalyze\Activity\HeartRate
	 */
	protected $HRavg;

	/**
	 * @var \Runalyze\Activity\HeartRate
	 */
	protected $HRmax;

	/**
	 * @var int [m]
	 */
	protected $ElevationUp = false;

	/**
	 * @var int [m]
	 */
	protected $ElevationDown = false;

	/**
	 * @var array
	 */
	protected $AdditionalValues = [];

	/**
	 * @var \Runalyze\Athlete
	 */
	protected $Athlete;

	/**
	 * @param int $duration [optional]
	 * @param float $distance [optional]
	 * @param LapIntensity|null $mode [optional]
	 */
	public function __construct($duration = 0, $distance = 0.0, ?LapIntensity $mode = null) {
		if (! $mode instanceof \Runalyze\Activity\LapIntensity) {
			$mode = LapIntensity::getInstanceActive();
		}

		$this->setDuration($duration);
		$this->setDistance($distance);
		$this->setMode($mode);

		$this->Athlete = Context::Athlete();
	}

	/**
	 * @param int $duration [s]
	 */
	public function setDuration($duration) {
		$this->LapDuration = new Duration($duration);
	}

	/**
	 * @param float $distance [km]
	 */
	public function setDistance($distance) {
		$this->LapDistance = new Distance($distance);
	}

	/**
	 * @param LapIntensity $mode
	 */
	public function setMode($mode) {
		$this->Mode = $mode;
	}

	/**
	 * @return LapIntensity
	 */
	public function getMode() {
		return $this->Mode;
	}

	/**
	 * @param int $duration [s]
	 */
	public function setTrackDuration($duration) {
		$this->TrackDurationAtEnd = new Duration($duration);
	}

	/**
	 * @param float $distance [km]
	 */
	public function setTrackDistance($distance) {
		$this->TrackDistanceAtEnd = new Distance($distance);
	}

	/**
	 * @var int
	 * @var int
	 */
	public function setHR($avg, $max) {
		$this->HRavg = new HeartRate($avg, $this->Athlete);
		$this->HRmax = new HeartRate($max, $this->Athlete);
	}

	/**
	 * @param int $up [m]
	 * @param int $down [m]
	 */
	public function setElevation($up, $down) {
		$this->ElevationUp = $up;
		$this->ElevationDown = $down;
	}

	/**
	 * @return \Runalyze\Activity\Duration
	 */
	public function duration() {
		return $this->LapDuration;
	}

	/**
	 * @return \Runalyze\Activity\Distance
	 */
	public function distance() {
		return $this->LapDistance;
	}

	/**
	 * @return \Runalyze\Activity\Pace
	 */
	public function pace() {
		if ($this->LapPace === null) {
			$this->LapPace = new Pace($this->LapDuration->seconds(), $this->LapDistance->kilometer());
		}

		return $this->LapPace;
	}

	/**
	 * @return \Runalyze\Activity\Duration
	 */
	public function trackDuration() {
		return $this->TrackDurationAtEnd;
	}

	/**
	 * @return \Runalyze\Activity\Distance
	 */
	public function trackDistance() {
		return $this->TrackDistanceAtEnd;
	}

	public function hasTrackValues(): bool {
		return $this->TrackDurationAtEnd !== null;
	}

	public function isActive(): bool {
		return $this->Mode->isNotRest();
	}

	public function hasHR(): bool {
		return $this->HRavg !== null;
	}

	/**
	 * @return \Runalyze\Activity\HeartRate
	 */
	public function HRavg() {
		return $this->HRavg;
	}

	/**
	 * @return \Runalyze\Activity\HeartRate
	 */
	public function HRmax() {
		return $this->HRmax;
	}

	public function hasElevation(): bool {
		return $this->ElevationUp !== false;
	}

	/**
	 * @return int [m]
	 */
	public function elevationUp() {
		return $this->ElevationUp;
	}

	/**
	 * @return int [m]
	 */
	public function elevationDown() {
		return $this->ElevationDown;
	}

	public function setAdditionalValues(array $data) {
		$this->AdditionalValues = $data;
	}

	/**
	 * @return array
	 */
	public function additionalValues() {
		return $this->AdditionalValues;
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setAdditionalValue($key, $value) {
		$this->AdditionalValues[$key] = $value;
	}

    /**
     * @param string $key
     */
    public function hasAdditionalValue($key): bool {
		return isset($this->AdditionalValues[$key]);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function additionalValue($key) {
		return $this->AdditionalValues[$key];
	}
}
